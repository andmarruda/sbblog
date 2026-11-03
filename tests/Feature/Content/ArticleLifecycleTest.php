<?php

namespace Tests\Feature\Content;

use App\Actions\Content\ArticleEditor;
use App\Enums\ArticleStatus;
use App\Enums\WorkspaceRole;
use App\Models\Article;
use App\Models\ArticleRevision;
use App\Models\AuthorProfile;
use App\Models\Blog;
use App\Models\Language;
use App\Models\Media;
use App\Models\OutboxEvent;
use App\Models\Tag;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsTenants;
use Tests\TestCase;

class ArticleLifecycleTest extends TestCase
{
    use BuildsTenants, RefreshDatabase;

    private User $owner;

    private Workspace $workspace;

    private Blog $blog;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedReferenceData();
        [$this->owner, $this->workspace, $this->blog] = $this->tenant();
        $this->blog->languages()->syncWithoutDetaching([$this->blog->default_language_id]);
    }

    private function url(string $path = ''): string
    {
        return "/{$this->workspace->slug}/{$this->blog->slug}/articles{$path}";
    }

    /**
     * @return array<string, mixed>
     */
    private function content(string $text = 'Hello', array $overrides = []): array
    {
        return [
            'title' => 'A title',
            'description' => null,
            'body' => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $text]]]]],
            'cover_media_id' => null,
            ...$overrides,
        ];
    }

    private function createArticle(?User $user = null): Article
    {
        $before = Article::withTrashed()->pluck('id');

        $this->actingAs($user ?? $this->owner)->post($this->url(), ['title' => 'My first post'])->assertRedirect();

        return Article::query()->whereNotIn('id', $before)->sole();
    }

    private function events(): array
    {
        return OutboxEvent::query()->whereBelongsTo($this->blog)->where('type', 'like', 'article.%')->orderBy('sequence')->pluck('type')->all();
    }

    public function test_creating_an_article_makes_a_draft_with_revision_one_and_an_author_profile(): void
    {
        $article = $this->createArticle();

        $this->assertSame(ArticleStatus::Draft, $article->status);
        $this->assertSame('my-first-post', $article->slug);
        $this->assertSame(1, $article->currentRevision?->number);
        $this->assertSame($this->owner->id, AuthorProfile::query()->findOrFail($article->author_profile_id)->user_id);
        $this->assertSame([], $this->events(), 'Drafts are not published.');
    }

    public function test_saving_creates_revisions_and_autosaves_coalesce(): void
    {
        $article = $this->createArticle();

        $this->actingAs($this->owner)->put($this->url("/{$article->id}/draft"), $this->content('one', ['autosave' => true]))->assertSessionHasNoErrors();
        $this->actingAs($this->owner)->put($this->url("/{$article->id}/draft"), $this->content('two', ['autosave' => true]));

        // Revision 1 (created a moment ago by the same user) was replaced twice: still one revision.
        $this->assertSame(1, $article->revisions()->count());
        $this->assertSame('two', $article->refresh()->currentRevision?->body['content'][0]['content'][0]['text']);

        $this->actingAs($this->owner)->put($this->url("/{$article->id}/draft"), $this->content('three'));
        $this->assertSame([1, 2], $article->revisions()->pluck('number')->all());
    }

    public function test_saving_identical_content_is_a_no_op(): void
    {
        $article = $this->createArticle();
        $this->actingAs($this->owner)->put($this->url("/{$article->id}/draft"), $this->content('same'));
        $revision = $article->refresh()->current_revision_id;

        $this->actingAs($this->owner)->put($this->url("/{$article->id}/draft"), $this->content('same'));

        $this->assertSame($revision, $article->refresh()->current_revision_id);
    }

    public function test_autosave_never_replaces_the_published_revision(): void
    {
        $article = $this->createArticle();
        $this->actingAs($this->owner)->post($this->url("/{$article->id}/publish"))->assertSessionHasNoErrors();
        $published = $article->refresh()->published_revision_id;

        $this->actingAs($this->owner)->put($this->url("/{$article->id}/draft"), $this->content('edit', ['autosave' => true]));

        $article->refresh();
        $this->assertSame($published, $article->published_revision_id);
        $this->assertNotSame($published, $article->current_revision_id);
        $this->assertTrue(ArticleRevision::query()->whereKey($published)->exists());
    }

    public function test_unsupported_body_and_foreign_media_are_rejected(): void
    {
        $article = $this->createArticle();
        $foreign = Media::factory()->create();

        $this->actingAs($this->owner)->put($this->url("/{$article->id}/draft"), $this->content(overrides: [
            'body' => ['type' => 'doc', 'content' => [['type' => 'script']]],
        ]))->assertSessionHasErrors('body');

        $this->actingAs($this->owner)->put($this->url("/{$article->id}/draft"), $this->content(overrides: [
            'body' => ['type' => 'doc', 'content' => [['type' => 'image', 'attrs' => ['mediaId' => $foreign->id]]]],
        ]))->assertSessionHasErrors('body');

        $this->actingAs($this->owner)->put($this->url("/{$article->id}/draft"), $this->content(overrides: [
            'cover_media_id' => $foreign->id,
        ]))->assertSessionHasErrors('cover_media_id');
    }

    public function test_publish_unpublish_and_republish_emit_events(): void
    {
        $article = $this->createArticle();

        $this->actingAs($this->owner)->post($this->url("/{$article->id}/publish"))->assertSessionHasNoErrors();
        $article->refresh();
        $this->assertSame(ArticleStatus::Published, $article->status);
        $this->assertSame($article->current_revision_id, $article->published_revision_id);
        $this->assertNotNull($article->published_at);

        $tag = Tag::factory()->for($this->blog)->create();
        $this->actingAs($this->owner)->patch($this->url("/{$article->id}"), ['tag_ids' => [$tag->id]])->assertSessionHasNoErrors();

        $this->actingAs($this->owner)->post($this->url("/{$article->id}/unpublish"))->assertSessionHasNoErrors();
        $this->assertSame(ArticleStatus::Draft, $article->refresh()->status);
        $this->assertNull($article->published_revision_id);

        $this->assertSame(['article.published', 'article.published', 'article.unpublished'], $this->events());
        $payload = OutboxEvent::query()->whereBelongsTo($this->blog)->where('type', 'article.published')->orderByDesc('sequence')->firstOrFail()->payload;
        $this->assertSame($tag->id, $payload['article']['tags'][0]['id']);
    }

    public function test_publishing_needs_a_title(): void
    {
        $article = $this->createArticle();
        $this->actingAs($this->owner)->put($this->url("/{$article->id}/draft"), $this->content(overrides: ['title' => '']));

        $this->actingAs($this->owner)->post($this->url("/{$article->id}/publish"))->assertSessionHasErrors('title');
        $this->assertSame([], $this->events());
    }

    public function test_scheduled_articles_are_published_by_the_scheduler(): void
    {
        $article = $this->createArticle();

        $this->actingAs($this->owner)->post($this->url("/{$article->id}/schedule"), ['scheduled_at' => now()->addHour()->toIso8601String()])
            ->assertSessionHasNoErrors();
        $this->assertSame(ArticleStatus::Scheduled, $article->refresh()->status);

        $this->artisan('articles:publish-scheduled')->assertSuccessful();
        $this->assertSame(ArticleStatus::Scheduled, $article->refresh()->status, 'Not yet.');

        $this->travel(2)->hours();
        $this->artisan('articles:publish-scheduled')->assertSuccessful();

        $this->assertSame(ArticleStatus::Published, $article->refresh()->status);
        $this->assertSame(['article.published'], $this->events());
    }

    public function test_scheduler_skips_articles_changed_after_being_picked(): void
    {
        $article = $this->createArticle();
        app(ArticleEditor::class)->schedule($article, now()->addMinute()->toImmutable(), $this->owner);
        $this->travel(2)->minutes();

        // An editor moves it back to draft between the scheduler's query and its lock.
        $article->forceFill(['status' => ArticleStatus::Draft, 'scheduled_at' => null])->save();

        $this->assertNull(app(ArticleEditor::class)->publish($article, onlyIfDue: true));
        $this->assertSame(ArticleStatus::Draft, $article->refresh()->status);
        $this->assertSame([], $this->events());
    }

    public function test_past_dates_cannot_be_scheduled(): void
    {
        $article = $this->createArticle();

        $this->actingAs($this->owner)->post($this->url("/{$article->id}/schedule"), ['scheduled_at' => now()->subHour()->toIso8601String()])
            ->assertSessionHasErrors('scheduled_at');
    }

    public function test_archiving_and_deleting_take_live_articles_down(): void
    {
        $first = $this->createArticle();
        $second = $this->createArticle();
        app(ArticleEditor::class)->publish($first);
        app(ArticleEditor::class)->publish($second);

        $this->actingAs($this->owner)->post($this->url("/{$first->id}/archive"));
        $this->actingAs($this->owner)->delete($this->url("/{$second->id}"))->assertRedirect($this->url());

        $this->assertSame(ArticleStatus::Archived, $first->refresh()->status);
        $this->assertSoftDeleted($second);
        $this->assertSame(['article.published', 'article.published', 'article.unpublished', 'article.unpublished'], $this->events());

        $this->actingAs($this->owner)->post($this->url("/{$second->id}/restore"))->assertSessionHasNoErrors();
        $this->assertNotSoftDeleted($second);
        $this->assertSame(ArticleStatus::Draft, $second->refresh()->status);
    }

    public function test_authors_edit_only_their_own_articles(): void
    {
        $author = $this->member($this->workspace, WorkspaceRole::Member, $this->blog, 'author');
        $own = $this->createArticle($author);
        $others = $this->createArticle();

        $this->actingAs($author)->put($this->url("/{$own->id}/draft"), $this->content('mine'))->assertSessionHasNoErrors();
        $this->actingAs($author)->put($this->url("/{$others->id}/draft"), $this->content('theirs'))->assertForbidden();
        $this->actingAs($author)->post($this->url("/{$own->id}/publish"))->assertForbidden();
        $this->actingAs($author)->post($this->url("/{$own->id}/review"))->assertSessionHasNoErrors();
        $this->assertSame(ArticleStatus::InReview, $own->refresh()->status);
    }

    public function test_viewers_cannot_create_or_edit(): void
    {
        $viewer = $this->member($this->workspace, WorkspaceRole::Member, $this->blog, 'viewer');
        $article = $this->createArticle();

        $this->actingAs($viewer)->get($this->url())->assertOk();
        $this->actingAs($viewer)->get($this->url("/{$article->id}"))->assertOk();
        $this->actingAs($viewer)->post($this->url(), ['title' => 'x'])->assertForbidden();
        $this->actingAs($viewer)->put($this->url("/{$article->id}/draft"), $this->content())->assertForbidden();
    }

    public function test_articles_from_another_blog_are_not_found(): void
    {
        $foreign = Article::factory()->create();

        $this->actingAs($this->owner)->get($this->url("/{$foreign->id}"))->assertNotFound();
    }

    public function test_bulk_actions_respect_permissions(): void
    {
        $editor = $this->member($this->workspace, WorkspaceRole::Member, $this->blog, 'author');
        $article = $this->createArticle();

        $this->actingAs($editor)->post($this->url('/bulk'), ['action' => 'delete', 'ids' => [$article->id]]);
        $this->assertNotSoftDeleted($article);

        $this->actingAs($this->owner)->post($this->url('/bulk'), ['action' => 'delete', 'ids' => [$article->id]]);
        $this->assertSoftDeleted($article);
    }

    public function test_translations_share_a_group_and_language_is_unique(): void
    {
        $english = Language::query()->where('code', 'en-US')->value('id');
        $this->blog->languages()->syncWithoutDetaching([$english]);
        $article = $this->createArticle();

        $this->actingAs($this->owner)->post($this->url("/{$article->id}/translations"), ['language_id' => $english])->assertRedirect();
        $translation = Article::query()->where('language_id', $english)->sole();

        $this->assertSame($article->translation_group_id, $translation->translation_group_id);
        $this->assertSame($article->slug, $translation->slug, 'Slugs are unique per language.');
        $this->assertSame('My first post', $translation->currentRevision?->title);

        $this->actingAs($this->owner)->post($this->url("/{$article->id}/translations"), ['language_id' => $english])
            ->assertSessionHasErrors('language_id');
    }

    public function test_old_revisions_can_be_compared_and_restored(): void
    {
        $article = $this->createArticle();
        $this->actingAs($this->owner)->put($this->url("/{$article->id}/draft"), $this->content('second'));
        $first = $article->revisions()->where('number', 1)->sole();

        $this->actingAs($this->owner)->get($this->url("/{$article->id}/revisions"))
            ->assertInertia(fn ($page) => $page->has('revisions', 2)->where('diff.body.0.op', 'insert')->where('diff.body.0.text', 'second'));

        $this->actingAs($this->owner)->post($this->url("/{$article->id}/revisions/{$first->id}/restore"))->assertRedirect();

        $article->refresh();
        $this->assertSame(3, $article->currentRevision?->number);
        $this->assertSame('My first post', $article->currentRevision?->title);
    }

    public function test_slug_must_be_unique_per_language(): void
    {
        $first = $this->createArticle();
        $second = $this->createArticle();

        $this->actingAs($this->owner)->patch($this->url("/{$second->id}"), ['slug' => $first->slug])->assertSessionHasErrors('slug');
    }

    public function test_list_filters_by_status_and_search(): void
    {
        $this->createArticle();
        $published = $this->createArticle();
        app(ArticleEditor::class)->publish($published);

        $this->actingAs($this->owner)->get($this->url('?status=published'))
            ->assertInertia(fn ($page) => $page->has('articles.data', 1)->where('articles.data.0.id', $published->id));

        $this->actingAs($this->owner)->get($this->url('?search=first'))
            ->assertInertia(fn ($page) => $page->has('articles.data', 2));
    }
}
