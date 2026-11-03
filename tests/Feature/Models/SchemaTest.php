<?php

namespace Tests\Feature\Models;

use App\Enums\ArticleStatus;
use App\Enums\IntegrationScope;
use App\Models\App;
use App\Models\AppInstallation;
use App\Models\Article;
use App\Models\ArticleRevision;
use App\Models\ArticleSuggestion;
use App\Models\AuditLog;
use App\Models\AuthorProfile;
use App\Models\Blog;
use App\Models\BlogTheme;
use App\Models\Category;
use App\Models\Comment;
use App\Models\EventTransport;
use App\Models\Frontend;
use App\Models\Integration;
use App\Models\Media;
use App\Models\OutboxEvent;
use App\Models\Role;
use App\Models\Snapshot;
use App\Models\SocialLink;
use App\Models\SuggestionComment;
use App\Models\Tag;
use App\Models\ThemeLicense;
use App\Models\TrustedDevice;
use App\Models\User;
use App\Models\WebauthnCredential;
use App\Models\Workspace;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SchemaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{class-string<Model>}>
     */
    public static function models(): array
    {
        return collect([
            User::class, Workspace::class, Blog::class, Role::class, Media::class, AuthorProfile::class,
            Category::class, Article::class, ArticleRevision::class, ArticleSuggestion::class,
            SuggestionComment::class, Tag::class, SocialLink::class, Integration::class, OutboxEvent::class,
            EventTransport::class, Frontend::class, Snapshot::class, App::class, AppInstallation::class,
            AuditLog::class, WebauthnCredential::class, TrustedDevice::class, Comment::class,
            ThemeLicense::class, BlogTheme::class,
        ])->mapWithKeys(fn (string $class) => [class_basename($class) => [$class]])->all();
    }

    /**
     * @param  class-string<Model>  $model
     */
    #[DataProvider('models')]
    public function test_factory_creates_a_valid_row(string $model): void
    {
        /** @phpstan-ignore staticMethod.notFound */
        $model::factory()->create();

        // Some factories create siblings too (an article brings its revision #1).
        $this->assertGreaterThanOrEqual(1, $model::query()->count());
    }

    public function test_uuid_keys_are_version_7(): void
    {
        $blog = Blog::factory()->create();

        $this->assertTrue(Str::isUuid($blog->id, 7));
    }

    public function test_article_factory_keeps_relations_in_one_blog_and_creates_revision_one(): void
    {
        $article = Article::factory()->published()->create();

        $this->assertSame($article->blog_id, $article->authorProfile?->blog_id);
        $this->assertSame($article->blog_id, $article->category?->blog_id);
        $this->assertSame(1, $article->currentRevision?->number);
        $this->assertSame($article->current_revision_id, $article->published_revision_id);
        $this->assertSame(ArticleStatus::Published, $article->status);
    }

    public function test_revisions_are_numbered_per_article_and_immutable(): void
    {
        $article = Article::factory()->create();
        $second = ArticleRevision::factory()->for($article)->create();

        $this->assertSame(2, $second->number);

        $this->expectException(LogicException::class);
        $second->update(['title' => 'changed']);
    }

    public function test_outbox_sequence_is_gapless_per_blog(): void
    {
        $blog = Blog::factory()->create();

        $events = OutboxEvent::factory()->for($blog)->count(3)->create();

        $this->assertSame([1, 2, 3], $events->pluck('sequence')->all());
        $this->assertSame(3, $blog->refresh()->event_sequence);
    }

    public function test_integration_scope_must_match_its_target(): void
    {
        $article = Article::factory()->create();

        $this->expectException(QueryException::class);
        Integration::factory()->for($article->blog)->create([
            'scope' => IntegrationScope::Blog,
            'article_id' => $article->id,
        ]);
    }

    public function test_custom_scripts_are_blog_scope_only(): void
    {
        $article = Article::factory()->create();

        $this->expectException(QueryException::class);
        Integration::factory()->custom()->forArticle($article)->create();
    }

    public function test_a_blog_has_only_one_active_theme(): void
    {
        $blog = Blog::factory()->create();
        BlogTheme::factory()->for($blog)->active()->create();

        $this->expectException(QueryException::class);
        BlogTheme::factory()->for($blog)->active()->create();
    }

    public function test_tokens_are_polymorphic_over_users_and_app_installations(): void
    {
        $user = User::factory()->create();
        $installation = AppInstallation::factory()->create();

        $user->createToken('cli');
        $installation->createToken('app', ['articles:read']);

        $this->assertSame('user', $user->tokens()->firstOrFail()->tokenable_type);
        $this->assertSame('app_installation', $installation->tokens()->firstOrFail()->tokenable_type);
    }

    public function test_database_seeder_runs(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(7, Role::query()->builtIn()->count());
        $this->assertTrue(Article::query()->published()->exists());
    }
}
