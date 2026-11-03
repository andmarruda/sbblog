<?php

namespace App\Migration\V1;

use App\Actions\Blogs\CreateBlog;
use App\Actions\Content\StoreMedia;
use App\Actions\Workspaces\CreateWorkspace;
use App\Enums\ArticleStatus;
use App\Enums\CommentPolicy;
use App\Enums\CommentStatus;
use App\Enums\IntegrationPlacement;
use App\Enums\IntegrationProvider;
use App\Enums\IntegrationScope;
use App\Enums\WorkspaceRole;
use App\Integrations\Providers;
use App\Jobs\BuildSnapshot;
use App\Models\Article;
use App\Models\AuthorProfile;
use App\Models\Blog;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Integration;
use App\Models\Language;
use App\Models\Media;
use App\Models\Role;
use App\Models\SocialLink;
use App\Models\SocialNetwork;
use App\Models\Tag;
use App\Models\User;
use App\Models\Workspace;
use App\Publishing\BlogState;
use App\Publishing\EventRecorder;
use App\Publishing\SchemaValidator;
use App\Support\Slug;
use App\Support\VisitorHash;
use Carbon\CarbonImmutable;
use Illuminate\Database\Connection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use stdClass;

/**
 * Imports a v1 database into one v2 blog, following the mapping in docs/v2/erd.md. Every v1 row
 * is recorded in `v1_import_map`, so running it again only adds what is new.
 */
class Importer
{
    private Connection $source;

    private ImportOptions $options;

    private ImportReport $report;

    private Workspace $workspace;

    private Blog $blog;

    private User $owner;

    private string $languageId;

    /** @var array<string, string|null> v1 image reference => media ID */
    private array $mediaCache = [];

    public function __construct(
        private readonly CreateWorkspace $createWorkspace,
        private readonly CreateBlog $createBlog,
        private readonly StoreMedia $storeMedia,
        private readonly EventRecorder $events,
        private readonly SchemaValidator $schemas,
    ) {}

    public function run(ImportOptions $options): ImportReport
    {
        $this->options = $options;
        $this->report = new ImportReport;
        $this->mediaCache = [];
        $this->source = DB::connection($options->connection);

        foreach (['users', 'generals', 'categories', 'articles', 'article_tags', 'article_comments'] as $table) {
            if (! $this->source->getSchemaBuilder()->hasTable($table)) {
                throw new RuntimeException("The v1 table [{$table}] was not found on connection [{$options->connection}].");
            }
        }

        $this->languageId = (string) (Language::query()->where('code', $options->language)->value('id')
            ?? throw new RuntimeException("Unknown language [{$options->language}]."));

        try {
            DB::transaction(function () {
                $users = $this->importUsers();
                $this->prepareBlog($users);
                $this->importMembers($users);
                $this->importBlogSettings();
                $categories = $this->importCategories();
                $authors = $this->importAuthors($users);
                $articles = $this->importArticles($users, $authors, $categories);
                $this->importTags($articles);
                $this->importComments($articles);

                if ($this->report->changed()) {
                    $this->publish();
                }

                if ($this->options->dryRun) {
                    throw new DryRunFinished;
                }
            });
        } catch (DryRunFinished) {
            // Everything was rolled back.
        }

        if (! $this->options->dryRun && $this->report->changed()) {
            BuildSnapshot::dispatch($this->blog);
        }

        return $this->report;
    }

    /**
     * @return array<int, User|null> v1 user ID => v2 user (null for deleted v1 users, who keep only an author profile)
     */
    private function importUsers(): array
    {
        $languages = $this->source->getSchemaBuilder()->hasTable('languages')
            ? $this->source->table('languages')->pluck('lang_id', 'id')->all()
            : [];
        $codes = ['en' => 'en-US', 'pt' => 'pt-BR', 'es' => 'es-ES'];
        $users = [];

        foreach ($this->source->table('users')->orderBy('id')->get() as $row) {
            if (($row->deleted_at ?? null) !== null) {
                $users[(int) $row->id] = null;
                $this->report->count('users', 'skipped');

                continue;
            }

            $email = Str::lower(trim((string) $row->email));
            $user = User::query()->where('email', $email)->first();

            if ($user === null) {
                $code = $codes[$languages[$row->language_id ?? 0] ?? ''] ?? null;
                $user = new User;
                // v1 already stored bcrypt hashes: copy them as they are, so passwords keep working.
                $user->forceFill([
                    'name' => (string) $row->name,
                    'email' => $email,
                    'email_verified_at' => now(),
                    'language_id' => $code === null ? null : Language::query()->where('code', $code)->value('id'),
                    'created_at' => $row->created_at ?? now(),
                ]);
                $user->setRawAttributes(['password' => (string) $row->password] + $user->getAttributes());
                $user->save();
                $this->report->count('users', 'created');
            } else {
                $this->report->count('users', 'existing');
            }

            $users[(int) $row->id] = $user;
        }

        return $users;
    }

    /**
     * @param  array<int, User|null>  $users
     */
    private function prepareBlog(array $users): void
    {
        $owner = $this->options->ownerEmail === null
            ? collect($users)->filter()->first()
            : User::query()->where('email', Str::lower($this->options->ownerEmail))->first();

        if (! $owner instanceof User) {
            throw new RuntimeException('No owner: pass --owner with the email of a v1 or v2 user.');
        }

        $this->owner = $owner;
        $general = $this->general();
        $name = (string) ($general->title ?? 'SBBlog');

        $workspace = Workspace::query()->where('slug', $this->options->workspace)->first()
            ?? $this->createWorkspace->handle($owner, $this->options->workspaceName ?? $name, $this->options->workspace);

        $this->blog = $workspace->blogs()->where('slug', $this->options->blog)->first()
            ?? $this->createBlog->handle($workspace, $owner, $name, $this->languageId, $this->options->blog);

        $this->workspace = $workspace;
    }

    /**
     * Every active v1 user becomes an Admin of the blog (and a member of the workspace).
     *
     * @param  array<int, User|null>  $users
     */
    private function importMembers(array $users): void
    {
        $workspace = $this->workspace;
        $admin = Role::query()->whereNull('workspace_id')->where('key', 'admin')->value('id')
            ?? throw new RuntimeException('Built-in roles are missing: run the RoleSeeder first.');

        foreach (array_filter($users) as $user) {
            if (! $workspace->members()->whereKey($user->id)->exists()) {
                $workspace->members()->attach($user->id, ['workspace_role' => WorkspaceRole::Member]);
            }

            if (! $this->blog->members()->whereKey($user->id)->exists()) {
                $this->blog->members()->attach($user->id, ['role_id' => $admin, 'invited_by' => $this->owner->id]);
                $this->report->count('members', 'created');
            } else {
                $this->report->count('members', 'existing');
            }
        }
    }

    private function importBlogSettings(): void
    {
        $general = $this->general();

        if ($this->mapped('generals', (int) $general->id) !== null) {
            $this->report->count('blog settings', 'existing');

            return;
        }

        $public = null;
        if (isset($general->comment_config_id) && $this->source->getSchemaBuilder()->hasTable('comment_configs')) {
            $public = $this->source->table('comment_configs')->where('id', $general->comment_config_id)->value('is_public');
        }

        $this->blog->fill([
            'title' => $general->title ?? $this->blog->title,
            'slogan' => $general->slogan ?? null,
            'description' => $general->description ?? null,
            'autoconvert_webp' => (bool) ($general->autoconvert_webp ?? true),
            'comment_policy' => $public === null ? $this->blog->comment_policy : ($public ? CommentPolicy::Open : CommentPolicy::Moderated),
            'brand_media_id' => $this->media((string) ($general->brand_image ?? ''), 'Brand') ?? $this->blog->brand_media_id,
        ])->save();

        $this->importSocialLinks((int) $general->id);
        $this->importIntegrations($general);
        $this->remember('generals', (int) $general->id, $this->blog->id);
        $this->report->count('blog settings', 'created');
    }

    private function importSocialLinks(int $generalId): void
    {
        if (! $this->source->getSchemaBuilder()->hasTable('social_network_urls')) {
            return;
        }

        $rows = $this->source->table('social_network_urls')
            ->join('social_networks', 'social_networks.id', '=', 'social_network_urls.social_network_id')
            ->where('general_id', $generalId)
            ->get(['social_networks.name', 'social_network_urls.url']);

        foreach ($rows as $row) {
            $key = match ($name = Str::lower(trim((string) $row->name))) {
                'twitter' => 'x',
                default => $name,
            };
            $network = SocialNetwork::query()->where('key', $key)->first();
            $url = trim((string) $row->url);

            if ($network === null || filter_var($url, FILTER_VALIDATE_URL) === false) {
                $this->report->warn('Social link skipped: '.$row->name.' '.$url);

                continue;
            }

            SocialLink::query()->updateOrCreate(
                ['blog_id' => $this->blog->id, 'author_profile_id' => null, 'social_network_id' => $network->id],
                ['url' => $url],
            );
            $this->report->count('social links', 'created');
        }
    }

    private function importIntegrations(stdClass $general): void
    {
        $analytics = (string) ($general->google_analytics ?? '');
        $ads = (string) ($general->google_ads_script ?? '');

        if (preg_match('/\bG-[A-Z0-9]{4,20}\b/', $analytics, $match) === 1) {
            $this->integration(IntegrationProvider::Ga4, ['measurement_id' => $match[0]]);
        } elseif (trim($analytics) !== '') {
            $this->report->warn('Google Analytics was not imported: only GA4 IDs (G-…) are supported; Universal Analytics stopped in 2023.');
        }

        if (preg_match('/\bAW-[0-9]{6,15}\b/', $ads, $match) === 1) {
            $this->integration(IntegrationProvider::GoogleAds, ['conversion_id' => $match[0], 'conversion_label' => null]);
        } elseif (trim($ads) !== '') {
            // Raw scripts need a person to check them: import them switched off.
            $this->integration(IntegrationProvider::Custom, ['name' => 'Google Ads (v1)', 'html' => mb_substr($ads, 0, 20000)], enabled: false);
            $this->report->warn('The Google Ads script was imported as a disabled custom script. Review it before turning it on.');
        }

        if (trim((string) ($general->google_optimize_script ?? '')) !== '') {
            $this->report->warn('The Google Optimize script was dropped: Google shut Optimize down in 2023.');
        }
    }

    /**
     * @param  array<string, string|null>  $config
     */
    private function integration(IntegrationProvider $provider, array $config, bool $enabled = true): void
    {
        Integration::query()->create([
            'blog_id' => $this->blog->id,
            'scope' => IntegrationScope::Blog,
            'provider' => $provider,
            'config' => $config,
            'placement' => IntegrationPlacement::Head,
            'consent_category' => Providers::definition($provider)['consent'],
            'enabled' => $enabled,
            'created_by' => $this->owner->id,
        ]);
        $this->report->count('integrations', 'created');
    }

    /**
     * @return array<int, string> v1 category ID => v2 category ID
     */
    private function importCategories(): array
    {
        $map = [];
        $position = (int) $this->blog->categories()->max('position');

        foreach ($this->source->table('categories')->orderBy('id')->get() as $row) {
            $id = (int) $row->id;

            if (($existing = $this->mapped('categories', $id)) !== null) {
                $map[$id] = $existing;
                $this->report->count('categories', 'existing');

                continue;
            }

            $name = Str::limit(trim((string) $row->category), 120, '');
            $category = new Category([
                'blog_id' => $this->blog->id,
                'name' => $name,
                'slug' => Slug::unique($name, fn (string $slug) => Category::withTrashed()->whereBelongsTo($this->blog)->where('slug', $slug)->exists()),
                'bgcolor' => $this->color($row->bgcolor ?? null),
                'position' => ++$position,
            ]);
            $category->created_at = $row->created_at ?? now();
            $category->save();

            if (($row->deleted_at ?? null) !== null) {
                $category->delete();
            }

            $map[$id] = $this->remember('categories', $id, $category->id);
            $this->report->count('categories', 'created');
        }

        return $map;
    }

    /**
     * One author profile per v1 user who wrote, including deleted users (without an account).
     *
     * @param  array<int, User|null>  $users
     * @return array<int, string> v1 user ID => author profile ID
     */
    private function importAuthors(array $users): array
    {
        $map = [];
        $writers = $this->source->table('articles')->distinct()->pluck('user_id')->map(fn ($id) => (int) $id)->all();

        foreach ($writers as $v1Id) {
            if (($existing = $this->mapped('authors', $v1Id)) !== null) {
                $map[$v1Id] = $existing;

                continue;
            }

            $user = $users[$v1Id] ?? null;
            $name = $user instanceof User
                ? $user->name
                : (string) ($this->source->table('users')->where('id', $v1Id)->value('name') ?? 'Former author');

            $author = AuthorProfile::query()->create([
                'blog_id' => $this->blog->id,
                'user_id' => $user?->id,
                'display_name' => $name,
                'slug' => Slug::unique($name, fn (string $slug) => AuthorProfile::query()->whereBelongsTo($this->blog)->where('slug', $slug)->exists()),
            ]);

            $map[$v1Id] = $this->remember('authors', $v1Id, $author->id);
            $this->report->count('authors', 'created');
        }

        return $map;
    }

    /**
     * @param  array<int, User|null>  $users
     * @param  array<int, string>  $authors
     * @param  array<int, string>  $categories
     * @return array<int, string> v1 article ID => v2 article ID
     */
    private function importArticles(array $users, array $authors, array $categories): array
    {
        $map = [];

        foreach ($this->source->table('articles')->orderBy('id')->cursor() as $row) {
            /** @var stdClass $row */
            $id = (int) $row->id;

            if (($existing = $this->mapped('articles', $id)) !== null) {
                $map[$id] = $existing;
                $this->report->count('articles', 'existing');

                continue;
            }

            $converter = new QuillToTiptap(fn (string $src) => $this->media($src, (string) $row->title));
            $body = $converter->convert((string) $row->article);

            if ($this->schemas->errors($body, 'body.schema.json') !== []) {
                // Should not happen; keep the words rather than lose the article.
                $text = trim(html_entity_decode(strip_tags((string) $row->article)));
                $body = ['type' => 'doc', 'content' => [$text === '' ? ['type' => 'paragraph'] : ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $text]]]]];
                $this->report->warn("Article {$id}: the body did not convert cleanly and was imported as plain text.");
            }

            foreach ($converter->warnings() as $warning) {
                $this->report->warn("Article {$id} ({$row->title}): {$warning}");
            }

            $createdAt = CarbonImmutable::parse($row->created_at ?? now());
            $premiere = ($row->premiere_date ?? null) === null ? null : CarbonImmutable::parse($row->premiere_date);
            $deleted = ($row->deleted_at ?? null) !== null;
            $scheduled = ! $deleted && $premiere !== null && $premiere->isFuture();
            $creator = $users[(int) $row->user_id] ?? null;

            $title = Str::limit(trim((string) $row->title), 255, '');
            $slugSource = trim((string) ($row->url_friendly ?? '')) !== '' ? (string) $row->url_friendly : $title;

            $article = new Article([
                'blog_id' => $this->blog->id,
                'author_profile_id' => $authors[(int) $row->user_id] ?? null,
                'category_id' => $categories[(int) $row->category_id] ?? null,
                'language_id' => $this->languageId,
                'slug' => Slug::unique($slugSource, fn (string $slug) => Article::withTrashed()->whereBelongsTo($this->blog)->where('language_id', $this->languageId)->where('slug', $slug)->exists(), maxLength: 200),
                'status' => $deleted ? ArticleStatus::Archived : ($scheduled ? ArticleStatus::Scheduled : ArticleStatus::Published),
                'bgcolor' => $this->color($row->bgcolor ?? null),
                'scheduled_at' => $scheduled ? $premiere : null,
                'published_at' => $scheduled ? null : ($premiere ?? $createdAt),
                'created_by' => $creator?->id,
            ]);
            $article->created_at = $createdAt;
            $article->updated_at = $row->updated_at ?? $createdAt;
            $article->save();

            $revision = $article->revisions()->create([
                'number' => 1,
                'title' => $title,
                'description' => ($row->description ?? null) === null ? null : Str::limit((string) $row->description, 500, ''),
                'body' => $body,
                'cover_media_id' => $this->media((string) ($row->cover_path ?? ''), $title),
                'created_by_user_id' => $creator?->id,
            ]);

            $article->forceFill([
                'current_revision_id' => $revision->id,
                'published_revision_id' => $scheduled ? null : $revision->id,
            ])->saveQuietly();

            if ($deleted) {
                $article->delete();
            }

            $map[$id] = $this->remember('articles', $id, $article->id);
            $this->report->count('articles', 'created');
        }

        return $map;
    }

    /**
     * Free-text v1 tags become one tag per blog for each distinct slug.
     *
     * @param  array<int, string>  $articles
     */
    private function importTags(array $articles): void
    {
        foreach ($this->source->table('article_tags')->orderBy('id')->get() as $row) {
            $articleId = $articles[(int) $row->article_id] ?? null;
            $name = trim((string) preg_replace('/\s+/', ' ', (string) $row->tag));
            $slug = Str::slug($name);

            if ($articleId === null || $slug === '' || $this->mapped('article_tags', (int) $row->id) !== null) {
                continue;
            }

            $tag = Tag::query()->firstOrCreate(['blog_id' => $this->blog->id, 'slug' => Str::limit($slug, 63, '')], ['name' => Str::limit($name, 100, '')]);
            $tag->wasRecentlyCreated ? $this->report->count('tags', 'created') : $this->report->count('tags', 'existing');
            $tag->articles()->syncWithoutDetaching([$articleId]);
            $this->remember('article_tags', (int) $row->id, $tag->id);
        }
    }

    /**
     * @param  array<int, string>  $articles
     */
    private function importComments(array $articles): void
    {
        foreach ($this->source->table('article_comments')->orderBy('id')->cursor() as $row) {
            /** @var stdClass $row */
            $id = (int) $row->id;
            $articleId = $articles[(int) $row->article_id] ?? null;

            if ($articleId === null || $this->mapped('comments', $id) !== null) {
                continue;
            }

            $createdAt = CarbonImmutable::parse($row->created_at ?? now());
            $comment = new Comment([
                'blog_id' => $this->blog->id,
                'article_id' => $articleId,
                'author_name' => Str::limit(trim((string) $row->comment_name), 120, ''),
                'body' => (string) $row->comment_text,
                'status' => ($row->authorized_public ?? false) ? CommentStatus::Approved : CommentStatus::Pending,
                'is_public' => (bool) ($row->comment_public ?? true),
                'ip_hash' => VisitorHash::ip(($row->user_ip ?? null) === null ? null : (string) $row->user_ip, $createdAt),
            ]);
            $comment->created_at = $createdAt;
            $comment->save();

            if (($row->deleted_at ?? null) !== null) {
                $comment->delete();
            }

            $this->remember('comments', $id, $comment->id);
            $this->report->count('comments', 'created');
        }
    }

    /**
     * Send the imported state to frontends: the same events a snapshot holds.
     */
    private function publish(): void
    {
        $blog = Blog::query()->whereKey($this->blog->id)->firstOrFail();

        foreach (BlogState::events($blog) as $event) {
            $this->events->record($blog, $event);
            $this->report->count('events', 'created');
        }
    }

    /**
     * Import an image v1 referenced: a data URI (Quill pasted images) or a file under the v1
     * storage folder. Remote URLs are not fetched.
     */
    private function media(string $reference, string $alt): ?string
    {
        $reference = trim($reference);

        if ($reference === '') {
            return null;
        }

        if (array_key_exists($reference, $this->mediaCache)) {
            return $this->mediaCache[$reference];
        }

        $path = null;

        if (preg_match('#^data:image/(png|jpe?g|gif|webp);base64,(.+)$#s', $reference, $match) === 1) {
            $bytes = base64_decode($match[2], true);
            if ($bytes !== false) {
                $path = tempnam(sys_get_temp_dir(), 'v1img').'.'.$match[1];
                file_put_contents($path, $bytes);
            }
        } elseif ($this->options->storagePath !== null) {
            // v1 kept files in storage/app/public and stored either a relative or an absolute path.
            $relative = preg_replace('#^(.*/)?(storage/app/public/|storage/|public/)#', '', parse_url($reference, PHP_URL_PATH) ?: $reference) ?? '';
            foreach ([$relative, basename($relative)] as $candidate) {
                $file = rtrim($this->options->storagePath, '/').'/'.ltrim($candidate, '/');
                if ($candidate !== '' && is_file($file) && ! str_contains($candidate, '..')) {
                    $path = $file;
                    break;
                }
            }
        }

        if ($path === null || @getimagesize($path) === false) {
            $this->report->warn('Image not found: '.Str::limit($reference, 80));

            return $this->mediaCache[$reference] = null;
        }

        $this->report->count('media', 'created');

        if ($this->options->dryRun) {
            // A row without a file, so references stay valid; the dry run rolls it back.
            return $this->mediaCache[$reference] = Media::query()->create([
                'blog_id' => $this->blog->id,
                'disk' => (string) config('sbblog.media.disk'),
                'path' => 'dry-run/'.basename($path),
                'mime_type' => 'image/png',
                'size_bytes' => (int) filesize($path),
            ])->id;
        }

        $media = $this->storeMedia->handle($this->blog, $this->owner, new UploadedFile($path, basename($path), null, null, true), Str::limit($alt, 120, ''));

        return $this->mediaCache[$reference] = $media->id;
    }

    private function general(): stdClass
    {
        /** @var stdClass|null $general */
        $general = $this->source->table('generals')->orderByDesc('active')->orderByDesc('id')->first();

        return $general ?? throw new RuntimeException('The v1 generals table is empty.');
    }

    private function color(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1 ? strtolower($value) : null;
    }

    private function mapped(string $source, int $id): ?string
    {
        $target = DB::table('v1_import_map')->where(['blog_id' => $this->blog->id, 'source' => $source, 'source_id' => $id])->value('target_id');

        return is_string($target) ? $target : null;
    }

    private function remember(string $source, int $id, string $target): string
    {
        DB::table('v1_import_map')->insert(['blog_id' => $this->blog->id, 'source' => $source, 'source_id' => $id, 'target_id' => $target]);

        return $target;
    }
}
