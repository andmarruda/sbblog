<?php

namespace Tests\Feature\Publishing;

use App\Enums\IntegrationScope;
use App\Models\Article;
use App\Models\AuthorProfile;
use App\Models\Blog;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Integration;
use App\Models\Language;
use App\Models\Media;
use App\Models\OutboxEvent;
use App\Models\SocialLink;
use App\Models\Tag;
use App\Models\User;
use App\Models\Workspace;
use App\Publishing\EventRecorder;
use App\Publishing\Events;
use App\Publishing\Events\PublishableEvent;
use App\Publishing\SchemaValidator;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Every event's payload must match its JSON Schema: the contract with @sbblog/core.
 */
class EventSchemasTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return list<PublishableEvent>
     */
    private function sampleEvents(): array
    {
        $blog = Blog::factory()->create();
        $author = AuthorProfile::factory()->for($blog)->create([
            'avatar_media_id' => Media::factory()->for($blog)->create(['variants' => ['webp' => 'media/a.webp']])->id,
        ]);
        SocialLink::factory()->forAuthor($author)->create();
        $parent = Category::factory()->for($blog)->create();
        $category = Category::factory()->for($blog)->create(['parent_id' => $parent->id]);
        $tag = Tag::factory()->for($blog)->create();
        $image = Media::factory()->for($blog)->create();

        $article = Article::factory()->published()->for($blog)->create([
            'author_profile_id' => $author->id,
            'category_id' => $category->id,
        ]);
        $article->tags()->attach($tag);
        $revision = $article->revisions()->create([
            'number' => 2,
            'title' => 'With an image',
            'body' => ['type' => 'doc', 'content' => [['type' => 'image', 'attrs' => ['mediaId' => $image->id, 'alt' => 'x']]]],
            'cover_media_id' => $image->id,
        ]);
        $article->update(['published_revision_id' => $revision->id, 'current_revision_id' => $revision->id]);

        $comment = Comment::factory()->approved()->for($article)->create(['blog_id' => $blog->id]);
        $integration = Integration::factory()->for($blog)->create();

        return [
            new Events\ArticlePublished($article->refresh()),
            new Events\ArticleUnpublished($article),
            new Events\CategoryUpserted($category),
            new Events\CategoryDeleted($category->id),
            new Events\TagUpserted($tag),
            new Events\TagDeleted($tag->id),
            new Events\AuthorUpserted($author),
            new Events\AuthorDeleted($author->id),
            new Events\BlogUpdated($blog),
            new Events\CommentApproved($comment),
            new Events\CommentRemoved($comment),
            new Events\IntegrationsUpdated($blog->id, IntegrationScope::Blog, null, collect([$integration])),
        ];
    }

    public function test_every_payload_matches_its_schema(): void
    {
        $validator = app(SchemaValidator::class);

        foreach ($this->sampleEvents() as $event) {
            $this->assertFileExists(resource_path('schemas/'.$event::schema()));
            $this->assertSame([], $validator->errors($event->payload(), $event::schema()), $event::type());
        }
    }

    public function test_every_event_type_has_a_schema_and_is_in_the_envelope(): void
    {
        $envelope = json_decode((string) file_get_contents(resource_path('schemas/events/envelope.schema.json')), true);
        $types = array_map(fn (PublishableEvent $event) => $event::type(), $this->sampleEvents());

        $this->assertEqualsCanonicalizing($envelope['properties']['type']['enum'], $types);
    }

    public function test_recorded_events_fit_the_envelope(): void
    {
        $blog = Blog::factory()->create();
        $event = DB::transaction(fn () => app(EventRecorder::class)->record($blog, new Events\BlogUpdated($blog)));

        $envelope = [
            'sequence' => $event->sequence,
            'type' => $event->type,
            'schema_version' => $event->schema_version,
            'aggregate_type' => $event->aggregate_type,
            'aggregate_id' => $event->aggregate_id,
            'occurred_at' => $event->occurred_at?->toIso8601String(),
            'payload' => $event->payload,
        ];

        $this->assertSame([], app(SchemaValidator::class)->errors($envelope, 'events/envelope.schema.json'));
    }

    public function test_article_payload_renders_html_and_media(): void
    {
        $article = $this->sampleEvents()[0]->payload()['article'];

        $this->assertStringContainsString('<figure><img', $article['html']);
        $this->assertCount(1, $article['media']);
        $this->assertSame(2, $article['revision']['number']);
        $this->assertNotNull($article['cover']);
    }

    public function test_blog_settings_emit_blog_updated(): void
    {
        $this->seed([LanguageSeeder::class, PermissionSeeder::class, RoleSeeder::class]);
        $owner = User::factory()->create();
        $workspace = Workspace::factory()->for($owner, 'owner')->create();
        $language = Language::query()->where('code', 'pt-BR')->value('id');
        $blog = Blog::factory()->for($workspace)->create(['default_language_id' => $language]);

        $this->actingAs($owner)->patch("/{$workspace->slug}/{$blog->slug}/settings/general", [
            'name' => 'Renamed', 'slug' => $blog->slug, 'default_language_id' => $language,
            'language_ids' => [$language], 'comment_policy' => 'open', 'autoconvert_webp' => true,
        ])->assertSessionHasNoErrors();

        $event = OutboxEvent::query()->whereBelongsTo($blog)->sole();
        $this->assertSame('blog.updated', $event->type);
        $this->assertSame('open', $event->payload['blog']['comment_policy']);
        $this->assertSame(1, $blog->fresh()?->event_sequence);
    }
}
