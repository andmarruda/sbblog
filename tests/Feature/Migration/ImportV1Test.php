<?php

namespace Tests\Feature\Migration;

use App\Enums\ArticleStatus;
use App\Enums\CommentPolicy;
use App\Enums\CommentStatus;
use App\Enums\IntegrationProvider;
use App\Models\Article;
use App\Models\AuthorProfile;
use App\Models\Blog;
use App\Models\Comment;
use App\Models\Integration;
use App\Models\OutboxEvent;
use App\Models\User;
use App\Models\Workspace;
use Database\Seeders\SocialNetworkSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\PendingCommand;
use Tests\Concerns\BuildsTenants;
use Tests\TestCase;

/**
 * Builds the final v1 schema (all migrations on main) in a separate Postgres schema and imports it.
 */
class ImportV1Test extends TestCase
{
    use BuildsTenants, RefreshDatabase;

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    private string $storage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedReferenceData();
        $this->seed(SocialNetworkSeeder::class);
        Storage::fake('public');
        config(['database.connections.v1' => ['search_path' => 'v1_test'] + config('database.connections.pgsql')]);
        DB::purge('v1');
        DB::connection('v1')->statement('DROP SCHEMA IF EXISTS v1_test CASCADE');
        DB::connection('v1')->statement('CREATE SCHEMA v1_test');

        $this->storage = sys_get_temp_dir().'/v1-storage-'.uniqid();
        mkdir($this->storage);
        file_put_contents($this->storage.'/cover.png', base64_decode(self::PNG));
        file_put_contents($this->storage.'/brand.png', base64_decode(self::PNG));

        $this->createV1Schema();
        $this->seedV1();
    }

    protected function tearDown(): void
    {
        DB::connection('v1')->statement('DROP SCHEMA IF EXISTS v1_test CASCADE');
        DB::disconnect('v1');
        array_map('unlink', glob($this->storage.'/*') ?: []);
        @rmdir($this->storage);

        parent::tearDown();
    }

    private function createV1Schema(): void
    {
        $schema = Schema::connection('v1');

        $schema->create('languages', function (Blueprint $table) {
            $table->id();
            $table->timestampsTz();
            $table->string('lang_id', 6);
            $table->string('label', 50);
            $table->string('icon', 50);
        });
        $schema->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->integer('language_id')->default(1);
            $table->rememberToken();
            $table->softDeletesTz();
            $table->timestampsTz();
        });
        $schema->create('comment_configs', function (Blueprint $table) {
            $table->id();
            $table->timestampsTz();
            $table->bigInteger('language_id');
            $table->string('description');
            $table->boolean('is_public')->default(true);
        });
        $schema->create('generals', function (Blueprint $table) {
            $table->id();
            $table->timestampsTz();
            $table->string('brand_image', 100);
            $table->string('slogan', 200);
            $table->string('section', 100);
            $table->boolean('active')->default(true);
            $table->string('google_analytics')->nullable();
            $table->string('google_ads_script')->nullable();
            $table->string('google_optimize_script')->nullable();
            $table->string('title', 110)->default('SBBlog');
            $table->string('description', 200)->default('SBblog');
            $table->boolean('autoconvert_webp')->default(true);
            $table->bigInteger('comment_config_id')->default(1);
        });
        $schema->create('social_networks', function (Blueprint $table) {
            $table->id();
            $table->timestampsTz();
            $table->string('icon', 45);
            $table->string('name', 50);
        });
        $schema->create('social_network_urls', function (Blueprint $table) {
            $table->id();
            $table->timestampsTz();
            $table->bigInteger('general_id');
            $table->bigInteger('social_network_id');
            $table->string('url', 250);
        });
        $schema->create('categories', function (Blueprint $table) {
            $table->id();
            $table->timestampsTz();
            $table->string('category', 50);
            $table->string('bgcolor', 7);
            $table->bigInteger('user_id');
            $table->softDeletesTz();
        });
        $schema->create('articles', function (Blueprint $table) {
            $table->id();
            $table->timestampsTz();
            $table->string('title', 150);
            $table->string('cover_path', 100);
            $table->bigInteger('category_id');
            $table->text('article');
            $table->bigInteger('user_id');
            $table->string('url_friendly', 150);
            $table->string('bgcolor', 7);
            $table->string('description', 200)->nullable();
            $table->timestampTz('premiere_date')->nullable();
            $table->softDeletesTz();
        });
        $schema->create('article_tags', function (Blueprint $table) {
            $table->id();
            $table->timestampsTz();
            $table->bigInteger('article_id');
            $table->string('tag', 30);
        });
        $schema->create('article_comments', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('article_id');
            $table->timestampsTz();
            $table->ipAddress('user_ip');
            $table->string('comment_name', 50);
            $table->string('comment_text', 350);
            $table->boolean('comment_public')->default(true);
            $table->boolean('authorized_public')->default(false);
            $table->softDeletesTz();
        });
    }

    private function seedV1(): void
    {
        $db = DB::connection('v1');
        $now = now();

        $db->table('languages')->insert([
            ['id' => 1, 'lang_id' => 'en', 'label' => 'English', 'icon' => 'en.png'],
            ['id' => 2, 'lang_id' => 'pt', 'label' => 'Português', 'icon' => 'pt.png'],
        ]);
        $db->table('users')->insert([
            ['id' => 1, 'name' => 'Alice', 'email' => 'Alice@Example.com', 'password' => Hash::make('secret-password'), 'language_id' => 2, 'deleted_at' => null, 'created_at' => $now],
            ['id' => 2, 'name' => 'Bob', 'email' => 'bob@example.com', 'password' => Hash::make('x'), 'language_id' => 1, 'deleted_at' => $now, 'created_at' => $now],
        ]);
        $db->table('comment_configs')->insert([
            ['id' => 1, 'language_id' => 1, 'description' => 'Public', 'is_public' => true],
            ['id' => 2, 'language_id' => 1, 'description' => 'Need admin validation', 'is_public' => false],
        ]);
        $db->table('generals')->insert([
            'id' => 1, 'brand_image' => '/var/www/html/storage/app/public/brand.png', 'slogan' => 'Old slogan', 'section' => 'x',
            'google_analytics' => 'G-ABC1234', 'google_ads_script' => '<script>ads()</script>', 'google_optimize_script' => 'OPT-1',
            'title' => 'Old Blog', 'description' => 'Old description', 'comment_config_id' => 2,
        ]);
        $db->table('social_networks')->insert([['id' => 1, 'icon' => 'tw.png', 'name' => 'Twitter'], ['id' => 2, 'icon' => 'ms.png', 'name' => 'Myspace']]);
        $db->table('social_network_urls')->insert([
            ['general_id' => 1, 'social_network_id' => 1, 'url' => 'https://twitter.com/oldblog'],
            ['general_id' => 1, 'social_network_id' => 2, 'url' => 'https://myspace.com/oldblog'],
        ]);
        $db->table('categories')->insert([
            ['id' => 1, 'category' => 'Tech', 'bgcolor' => '#112233', 'user_id' => 1, 'deleted_at' => null],
            ['id' => 2, 'category' => 'Old stuff', 'bgcolor' => '#445566', 'user_id' => 1, 'deleted_at' => $now],
        ]);
        $db->table('articles')->insert([
            [
                'id' => 1, 'title' => 'Hello world', 'cover_path' => 'cover.png', 'category_id' => 1, 'user_id' => 1, 'url_friendly' => 'hello-world',
                'bgcolor' => '#abcdef', 'description' => 'First post', 'premiere_date' => $now->copy()->subYear(), 'deleted_at' => null, 'created_at' => $now->copy()->subYear(),
                'article' => '<h1>Intro</h1><p>Some <strong>bold</strong> text.</p><p><img src="data:image/png;base64,'.self::PNG.'"></p>'
                    .'<iframe class="ql-video" src="https://www.youtube.com/embed/dQw4w9WgXcQ"></iframe><ul><li>one</li><li class="ql-indent-1">two</li></ul>',
            ],
            [
                'id' => 2, 'title' => 'Coming soon', 'cover_path' => 'missing.png', 'category_id' => 2, 'user_id' => 2, 'url_friendly' => 'hello-world',
                'bgcolor' => 'red', 'description' => null, 'premiere_date' => $now->copy()->addWeek(), 'deleted_at' => null, 'created_at' => $now,
                'article' => '<p>Not yet.</p>',
            ],
            [
                'id' => 3, 'title' => 'Removed', 'cover_path' => 'cover.png', 'category_id' => 1, 'user_id' => 1, 'url_friendly' => 'removed',
                'bgcolor' => '#000000', 'description' => null, 'premiere_date' => null, 'deleted_at' => $now, 'created_at' => $now,
                'article' => '<p>Gone.</p>',
            ],
        ]);
        $db->table('article_tags')->insert([
            ['article_id' => 1, 'tag' => 'Laravel'], ['article_id' => 1, 'tag' => ' laravel '], ['article_id' => 1, 'tag' => 'PHP'], ['article_id' => 2, 'tag' => 'php'],
        ]);
        $db->table('article_comments')->insert([
            ['article_id' => 1, 'user_ip' => '203.0.113.9', 'comment_name' => 'Reader', 'comment_text' => 'Nice!', 'comment_public' => true, 'authorized_public' => true, 'deleted_at' => null, 'created_at' => $now],
            ['article_id' => 1, 'user_ip' => '203.0.113.9', 'comment_name' => 'Waiting', 'comment_text' => 'Hm', 'comment_public' => true, 'authorized_public' => false, 'deleted_at' => null, 'created_at' => $now],
            ['article_id' => 1, 'user_ip' => '203.0.113.9', 'comment_name' => 'Spam', 'comment_text' => 'Buy', 'comment_public' => true, 'authorized_public' => false, 'deleted_at' => $now, 'created_at' => $now],
        ]);
    }

    private function import(array $options = []): PendingCommand
    {
        return $this->artisan('sbblog:import-v1', ['connection' => 'v1', '--workspace' => 'legacy', '--blog' => 'old-blog', '--storage' => $this->storage] + $options);
    }

    private function blog(): Blog
    {
        return Workspace::query()->where('slug', 'legacy')->sole()->blogs()->where('slug', 'old-blog')->sole();
    }

    public function test_imports_the_v1_database(): void
    {
        $this->import()->assertSuccessful();

        $blog = $this->blog();
        $alice = User::query()->where('email', 'alice@example.com')->sole();

        // Users keep their passwords; deleted users only remain as authors.
        $this->assertTrue(Hash::check('secret-password', $alice->password));
        $this->assertNull(User::query()->where('email', 'bob@example.com')->first());
        $this->assertTrue($blog->members()->whereKey($alice->id)->exists());
        $this->assertNull(AuthorProfile::query()->whereBelongsTo($blog)->where('display_name', 'Bob')->sole()->user_id);

        // Blog settings, brand, social links and integrations.
        $this->assertSame('Old slogan', $blog->slogan);
        $this->assertSame(CommentPolicy::Moderated, $blog->comment_policy);
        $this->assertNotNull($blog->brand_media_id);
        $this->assertSame(['x'], $blog->socialLinks()->with('socialNetwork')->get()->pluck('socialNetwork.key')->all());
        $integrations = Integration::query()->whereBelongsTo($blog)->get()->keyBy(fn (Integration $integration) => $integration->provider->value);
        $this->assertSame('G-ABC1234', $integrations['ga4']->config['measurement_id']);
        $this->assertFalse($integrations[IntegrationProvider::Custom->value]->enabled);

        // Categories, including the deleted one.
        $this->assertSame(2, $blog->categories()->withTrashed()->count());
        $this->assertSame(1, $blog->categories()->count());

        // Articles: body converted, images imported, statuses mapped, slugs unique.
        $articles = Article::withTrashed()->whereBelongsTo($blog)->with('currentRevision')->get()->keyBy(fn (Article $article) => $article->currentRevision?->title);
        $hello = $articles['Hello world'];
        $this->assertSame(ArticleStatus::Published, $hello->status);
        $this->assertSame('hello-world', $hello->slug);
        $this->assertNotNull($hello->currentRevision?->cover_media_id);
        $types = array_column($hello->currentRevision->body['content'], 'type');
        $this->assertSame(['heading', 'paragraph', 'image', 'embed', 'bulletList'], $types);
        $this->assertSame(ArticleStatus::Scheduled, $articles['Coming soon']->status);
        $this->assertNull($articles['Coming soon']->published_revision_id);
        $this->assertNotSame('hello-world', $articles['Coming soon']->slug);
        $this->assertTrue($articles['Removed']->trashed());
        $this->assertSame(ArticleStatus::Archived, $articles['Removed']->status);

        // Tags normalized per blog.
        $this->assertEqualsCanonicalizing(['laravel', 'php'], $blog->tags()->pluck('slug')->all());
        $this->assertSame(2, $hello->tags()->count());

        // Comments: statuses, trash and hashed IPs.
        $comments = Comment::withTrashed()->whereBelongsTo($blog)->get();
        $this->assertCount(3, $comments);
        $this->assertSame(1, $comments->where('status', CommentStatus::Approved)->count());
        $this->assertSame(1, $comments->filter->trashed()->count());
        $this->assertFalse($comments->contains(fn (Comment $comment) => str_contains((string) $comment->ip_hash, '203.0.113')));

        // Frontends get the imported state.
        $this->assertSame(1, OutboxEvent::query()->whereBelongsTo($blog)->where('type', 'article.published')->count());
        $this->assertSame(1, OutboxEvent::query()->whereBelongsTo($blog)->where('type', 'comment.approved')->count());
    }

    public function test_running_again_adds_nothing(): void
    {
        $this->import()->assertSuccessful();
        $events = OutboxEvent::query()->count();

        DB::connection('v1')->table('article_comments')->insert(['article_id' => 1, 'user_ip' => '198.51.100.1', 'comment_name' => 'Late', 'comment_text' => 'New one', 'authorized_public' => true, 'created_at' => now()]);
        $this->import()->assertSuccessful();

        $blog = $this->blog();
        $this->assertSame(3, Article::withTrashed()->whereBelongsTo($blog)->count());
        $this->assertSame(2, $blog->categories()->withTrashed()->count());
        $this->assertSame(4, Comment::withTrashed()->whereBelongsTo($blog)->count());
        $this->assertSame(2, Integration::query()->whereBelongsTo($blog)->count());
        $this->assertGreaterThan($events, OutboxEvent::query()->count(), 'the new comment is published');

        $events = OutboxEvent::query()->count();
        $this->import()->assertSuccessful();
        $this->assertSame($events, OutboxEvent::query()->count());
    }

    public function test_dry_run_saves_nothing(): void
    {
        $this->import(['--dry-run' => true])
            ->expectsOutputToContain('Dry run')
            ->assertSuccessful();

        $this->assertNull(Workspace::query()->where('slug', 'legacy')->first());
        $this->assertNull(User::query()->where('email', 'alice@example.com')->first());
        Storage::disk('public')->assertDirectoryEmpty('/');
    }

    public function test_verification_compares_both_sides(): void
    {
        $this->import()->assertSuccessful();

        $this->artisan('sbblog:verify-v1', ['connection' => 'v1', '--workspace' => 'legacy', '--blog' => 'old-blog'])
            ->expectsOutputToContain('Everything from v1 is in v2.')
            ->assertSuccessful();

        DB::connection('v1')->table('articles')->where('id', 1)->update(['article' => '<p>Completely different words now.</p>']);

        $this->artisan('sbblog:verify-v1', ['connection' => 'v1', '--workspace' => 'legacy', '--blog' => 'old-blog'])
            ->expectsOutputToContain('Verification found differences.')
            ->assertFailed();
    }
}
