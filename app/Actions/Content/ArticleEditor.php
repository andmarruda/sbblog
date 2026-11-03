<?php

namespace App\Actions\Content;

use App\Enums\ArticleStatus;
use App\Models\AppInstallation;
use App\Models\Article;
use App\Models\ArticleRevision;
use App\Models\ArticleSuggestion;
use App\Models\Blog;
use App\Models\User;
use App\Publishing\Body\TiptapDocument;
use App\Publishing\EventRecorder;
use App\Publishing\Events\ArticlePublished;
use App\Publishing\Events\ArticleUnpublished;
use App\Support\AuditLogger;
use App\Support\Slug;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Article lifecycle. Content lives in immutable revisions; `current_revision_id` is the latest
 * draft and `published_revision_id` what readers see. Every change readers can see is recorded
 * in the blog's event log in the same transaction.
 */
class ArticleEditor
{
    /** Autosaves by the same person within this window replace their previous autosave. */
    public const COALESCE_MINUTES = 10;

    public function __construct(
        private readonly EventRecorder $events,
        private readonly AuthorProfiles $authors,
        private readonly AuditLogger $audit,
    ) {}

    public function create(Blog $blog, User $user, string $title, ?string $languageId = null): Article
    {
        return DB::transaction(function () use ($blog, $user, $title, $languageId) {
            $languageId ??= $blog->default_language_id;

            $authorId = $this->authors->forUser($blog, $user)->id;

            $article = $this->withUniqueSlug(fn (bool $randomize) => Article::query()->create([
                'blog_id' => $blog->id,
                'author_profile_id' => $authorId,
                'language_id' => $languageId,
                'slug' => $this->uniqueSlug($blog, $languageId, $title, $randomize),
                'status' => ArticleStatus::Draft,
                'created_by' => $user->id,
            ]));

            $this->newRevision($article, $user, ['title' => $title, 'description' => null, 'body' => TiptapDocument::empty(), 'cover_media_id' => null]);
            $this->audit->record('article.created', $article, ['title' => $title], blog: $blog, actor: $user);

            return $article;
        });
    }

    /**
     * Save the editor content as a new revision. Returns the current revision unchanged when
     * nothing changed.
     *
     * @param  array{title: string, description: string|null, body: array<string, mixed>, cover_media_id: string|null}  $content
     */
    public function saveDraft(Article $article, User|AppInstallation $user, array $content, bool $coalesce = false): ArticleRevision
    {
        return DB::transaction(function () use ($article, $user, $content, $coalesce) {
            $article = Article::query()->lockForUpdate()->findOrFail($article->id);
            $current = $article->currentRevision;

            if ($current !== null && $this->sameContent($current, $content)) {
                return $current;
            }

            $replace = $coalesce && $user instanceof User && $current !== null && $this->canReplace($article, $current, $user);

            if ($replace) {
                $number = $current->number;
                $article->forceFill(['current_revision_id' => null])->save();
                $current->delete();
            }

            return $this->newRevision($article, $user, $content, $number ?? null);
        });
    }

    /**
     * Fields that live on the article itself. Live articles are re-published right away, since
     * readers see these without a new revision.
     *
     * @param  array{slug?: string, category_id?: string|null, author_profile_id?: string, bgcolor?: string|null, tag_ids?: list<string>}  $data
     */
    public function updateDetails(Article $article, array $data): Article
    {
        return DB::transaction(function () use ($article, $data) {
            $tags = $data['tag_ids'] ?? null;
            unset($data['tag_ids']);

            $article->fill($data)->save();

            if ($tags !== null) {
                $article->tags()->sync($tags);
                $article->unsetRelation('tags');
            }

            if ($this->isLive($article)) {
                $this->events->record($this->blog($article), new ArticlePublished($article));
            }

            return $article;
        });
    }

    /**
     * @param  bool  $onlyIfDue  Scheduler mode: publish only if still scheduled and due once locked,
     *                           since an editor may have changed it after the article was picked.
     */
    public function publish(Article $article, ?User $user = null, bool $onlyIfDue = false): ?Article
    {
        return DB::transaction(function () use ($article, $user, $onlyIfDue) {
            $article = Article::query()->lockForUpdate()->findOrFail($article->id);

            if ($onlyIfDue && ($article->status !== ArticleStatus::Scheduled || $article->scheduled_at === null || $article->scheduled_at->isFuture())) {
                return null;
            }

            $revision = $article->currentRevision;

            if ($revision === null || trim($revision->title) === '') {
                throw ValidationException::withMessages(['title' => __('Give the article a title before publishing.')]);
            }

            TiptapDocument::validate($revision->body);

            $article->forceFill([
                'published_revision_id' => $revision->id,
                'status' => ArticleStatus::Published,
                'published_at' => $article->published_at ?? now(),
                'scheduled_at' => null,
            ])->save();

            $this->events->record($this->blog($article), new ArticlePublished($article->setRelation('publishedRevision', $revision)));
            $this->audit->record('article.published', $article, ['revision' => $revision->number], blog: $this->blog($article), actor: $user);

            return $article;
        });
    }

    public function schedule(Article $article, CarbonInterface $at, User $user): Article
    {
        if ($this->isLive($article)) {
            throw ValidationException::withMessages(['scheduled_at' => __('Unpublish the article before scheduling it.')]);
        }

        if ($at->isPast()) {
            throw ValidationException::withMessages(['scheduled_at' => __('Choose a date in the future.')]);
        }

        $article->forceFill(['status' => ArticleStatus::Scheduled, 'scheduled_at' => $at])->save();
        $this->audit->record('article.scheduled', $article, ['at' => $at->toIso8601String()], blog: $this->blog($article), actor: $user);

        return $article;
    }

    /**
     * Back to draft: takes a live article off the site, or cancels a schedule or a review.
     */
    public function unpublish(Article $article, User $user): Article
    {
        return $this->takeDown($article, $user, ArticleStatus::Draft, 'article.unpublished');
    }

    public function archive(Article $article, User $user): Article
    {
        return $this->takeDown($article, $user, ArticleStatus::Archived, 'article.archived');
    }

    public function submitForReview(Article $article, User $user): Article
    {
        if ($this->isLive($article)) {
            throw ValidationException::withMessages(['status' => __('This article is already published.')]);
        }

        $article->forceFill(['status' => ArticleStatus::InReview, 'scheduled_at' => null])->save();
        $this->audit->record('article.submitted', $article, blog: $this->blog($article), actor: $user);

        return $article;
    }

    public function delete(Article $article, User $user): void
    {
        DB::transaction(function () use ($article, $user) {
            if ($this->isLive($article)) {
                $this->events->record($this->blog($article), new ArticleUnpublished($article));
            }

            $article->forceFill(['published_revision_id' => null, 'scheduled_at' => null, 'status' => ArticleStatus::Draft])->save();
            $article->delete();
            $this->audit->record('article.deleted', $article, blog: $this->blog($article), actor: $user);
        });
    }

    public function restore(Article $article, User $user): void
    {
        $article->restore();
        $this->audit->record('article.restored', $article, blog: $this->blog($article), actor: $user);
    }

    /**
     * Start a translation: a new draft in the same translation group, copied from this one.
     */
    public function translate(Article $source, string $languageId, User $user): Article
    {
        $blog = $this->blog($source);

        if (! $blog->languages()->whereKey($languageId)->exists()) {
            throw ValidationException::withMessages(['language_id' => __('The blog does not use this language.')]);
        }

        if (Article::withTrashed()->where('translation_group_id', $source->translation_group_id)->where('language_id', $languageId)->exists()) {
            throw ValidationException::withMessages(['language_id' => __('This article already has a translation in this language.')]);
        }

        return DB::transaction(function () use ($source, $languageId, $user, $blog) {
            $revision = $source->currentRevision;

            $authorId = $this->authors->forUser($blog, $user)->id;

            $article = $this->withUniqueSlug(fn (bool $randomize) => Article::query()->create([
                'blog_id' => $blog->id,
                'author_profile_id' => $authorId,
                'category_id' => $source->category_id,
                'language_id' => $languageId,
                'translation_group_id' => $source->translation_group_id,
                'slug' => $this->uniqueSlug($blog, $languageId, $source->slug, $randomize),
                'status' => ArticleStatus::Draft,
                'bgcolor' => $source->bgcolor,
                'created_by' => $user->id,
            ]));

            $article->tags()->sync($source->tags()->pluck('tags.id'));

            $this->newRevision($article, $user, [
                'title' => $revision->title ?? '',
                'description' => $revision?->description,
                'body' => $revision->body ?? TiptapDocument::empty(),
                'cover_media_id' => $revision?->cover_media_id,
            ]);

            $this->audit->record('article.translated', $article, ['from' => $source->id], blog: $blog, actor: $user);

            return $article;
        });
    }

    /**
     * Bring an old revision back as a new draft on top of the latest one.
     */
    public function restoreRevision(Article $article, ArticleRevision $revision, User $user): ArticleRevision
    {
        return $this->saveDraft($article, $user, [
            'title' => $revision->title,
            'description' => $revision->description,
            'body' => $revision->body,
            'cover_media_id' => $revision->cover_media_id,
        ]);
    }

    /**
     * Publish every scheduled article whose time has come. Called by the scheduler.
     */
    public function publishDue(): int
    {
        $published = 0;

        Article::query()
            ->where('status', ArticleStatus::Scheduled)
            ->where('scheduled_at', '<=', now())
            ->whereHas('blog', fn ($query) => $query->active())
            ->orderBy('scheduled_at')
            ->each(function (Article $article) use (&$published) {
                try {
                    if ($this->publish($article, onlyIfDue: true) !== null) {
                        $published++;
                    }
                } catch (ValidationException $exception) {
                    // Back to draft, so it isn't retried every minute; the audit log says why.
                    $article->forceFill(['status' => ArticleStatus::Draft, 'scheduled_at' => null])->save();
                    $this->audit->record('article.schedule_failed', $article, ['errors' => $exception->errors()], blog: $this->blog($article), actor: null);
                }
            });

        return $published;
    }

    public function isLive(Article $article): bool
    {
        return $article->status === ArticleStatus::Published && $article->published_revision_id !== null;
    }

    /**
     * @param  array{title: string, description: string|null, body: array<string, mixed>, cover_media_id: string|null}  $content
     */
    private function newRevision(Article $article, User|AppInstallation $user, array $content, ?int $number = null): ArticleRevision
    {
        $revision = $article->revisions()->create([
            ...$content,
            'number' => $number ?? (int) $article->revisions()->max('number') + 1,
            'created_by_user_id' => $user instanceof User ? $user->id : null,
            'created_by_app_installation_id' => $user instanceof AppInstallation ? $user->id : null,
        ]);

        $article->forceFill(['current_revision_id' => $revision->id])->save();
        $article->setRelation('currentRevision', $revision);

        return $revision;
    }

    private function canReplace(Article $article, ArticleRevision $current, User $user): bool
    {
        return $current->id !== $article->published_revision_id
            && $current->created_by_user_id === $user->id
            && $current->created_at !== null
            && $current->created_at->greaterThan(now()->subMinutes(self::COALESCE_MINUTES))
            && ! ArticleSuggestion::query()
                ->where(fn ($query) => $query->where('base_revision_id', $current->id)->orWhere('proposed_revision_id', $current->id))
                ->exists();
    }

    /**
     * @param  array{title: string, description: string|null, body: array<string, mixed>, cover_media_id: string|null}  $content
     */
    private function sameContent(ArticleRevision $revision, array $content): bool
    {
        return $revision->title === $content['title']
            && $revision->description === $content['description']
            && $revision->cover_media_id === $content['cover_media_id']
            && json_encode(self::canonical($revision->body)) === json_encode(self::canonical($content['body']));
    }

    /**
     * Postgres jsonb reorders object keys, so compare with keys sorted.
     */
    private static function canonical(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (! array_is_list($value)) {
            ksort($value);
        }

        return array_map(self::canonical(...), $value);
    }

    private function takeDown(Article $article, User $user, ArticleStatus $status, string $action): Article
    {
        return DB::transaction(function () use ($article, $user, $status, $action) {
            if ($this->isLive($article)) {
                $this->events->record($this->blog($article), new ArticleUnpublished($article));
            }

            $article->forceFill(['status' => $status, 'published_revision_id' => null, 'scheduled_at' => null])->save();
            $this->audit->record($action, $article, blog: $this->blog($article), actor: $user);

            return $article;
        });
    }

    /**
     * Two articles with the same title created at once can both pass the slug check. On a
     * collision, retry the insert (in a savepoint, so the outer transaction survives) with a
     * random suffix, which concurrent writers can't predict.
     *
     * @param  Closure(bool): Article  $create  Receives whether to randomize the slug.
     */
    private function withUniqueSlug(Closure $create): Article
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                return DB::transaction(fn () => $create($attempt > 1));
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt >= 5) {
                    throw $exception;
                }
            }
        }
    }

    private function uniqueSlug(Blog $blog, string $languageId, string $source, bool $randomize = false): string
    {
        $source = $source !== '' ? $source : 'untitled';

        return Slug::unique(
            $randomize ? $source.' '.Str::lower(Str::random(5)) : $source,
            fn (string $slug) => Article::withTrashed()->whereBelongsTo($blog)->where('language_id', $languageId)->where('slug', $slug)->exists(),
            maxLength: 120,
        );
    }

    private function blog(Article $article): Blog
    {
        /** @var Blog */
        return $article->blog()->firstOrFail();
    }
}
