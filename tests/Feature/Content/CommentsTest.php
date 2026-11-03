<?php

namespace Tests\Feature\Content;

use App\Actions\Content\ArticleEditor;
use App\Enums\CommentPolicy;
use App\Enums\CommentStatus;
use App\Enums\WorkspaceRole;
use App\Models\Article;
use App\Models\Blog;
use App\Models\Comment;
use App\Models\Frontend;
use App\Models\OutboxEvent;
use App\Support\SyncToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsTenants;
use Tests\TestCase;

class CommentsTest extends TestCase
{
    use BuildsTenants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedReferenceData();
    }

    /**
     * @return array{Blog, Article, string}
     */
    private function liveArticle(CommentPolicy $policy = CommentPolicy::Moderated): array
    {
        [, , $blog] = $this->tenant();
        $blog->update(['comment_policy' => $policy]);
        $article = Article::factory()->for($blog)->create();
        app(ArticleEditor::class)->publish($article);
        $token = SyncToken::generate();
        Frontend::factory()->for($blog)->create(['sync_token_hash' => SyncToken::hash($token)]);

        return [$blog, $article, $token];
    }

    /**
     * @return list<string>
     */
    private function events(Blog $blog): array
    {
        return OutboxEvent::query()->whereBelongsTo($blog)->where('type', 'like', 'comment.%')->orderBy('sequence')->pluck('type')->all();
    }

    public function test_moderated_blogs_queue_comments_and_hash_reader_data(): void
    {
        [$blog, $article, $token] = $this->liveArticle();

        $this->withToken($token)->withHeader('X-SBBlog-Client-IP', '203.0.113.7')->postJson('/api/comments', [
            'article_id' => $article->id, 'author_name' => 'Ana', 'author_email' => 'Ana@Example.com', 'body' => 'Great post',
        ])->assertCreated()->assertJsonPath('status', 'pending');

        $comment = Comment::query()->sole();
        $this->assertFalse($comment->is_public);
        $this->assertSame(hash('sha256', 'ana@example.com'), $comment->getRawOriginal('author_email_hash'));
        $this->assertNotSame('203.0.113.7', $comment->getRawOriginal('ip_hash'));
        $this->assertSame([], $this->events($blog));
    }

    public function test_open_blogs_publish_at_once_and_disabled_blogs_refuse(): void
    {
        [$blog, $article, $token] = $this->liveArticle(CommentPolicy::Open);

        $this->withToken($token)->postJson('/api/comments', ['article_id' => $article->id, 'author_name' => 'Ana', 'body' => 'Hi there'])
            ->assertJsonPath('status', 'approved');
        $this->assertSame(['comment.approved'], $this->events($blog));

        $blog->update(['comment_policy' => CommentPolicy::Disabled]);
        $this->withToken($token)->postJson('/api/comments', ['article_id' => $article->id, 'author_name' => 'Ana', 'body' => 'Hi again'])
            ->assertForbidden();
    }

    public function test_spam_traps_and_rate_limits(): void
    {
        [, $article, $token] = $this->liveArticle();
        $payload = ['article_id' => $article->id, 'author_name' => 'Bot', 'body' => 'Buy now'];

        $this->withToken($token)->postJson('/api/comments', [...$payload, 'website' => 'http://spam.test'])->assertUnprocessable();

        foreach (range(1, 5) as $i) {
            $this->withToken($token)->postJson('/api/comments', $payload)->assertCreated();
        }
        $this->withToken($token)->postJson('/api/comments', $payload)->assertStatus(429);
    }

    public function test_comments_only_on_live_articles_of_the_frontend_blog(): void
    {
        [, , $token] = $this->liveArticle();
        $foreign = Article::factory()->create();
        app(ArticleEditor::class)->publish($foreign);
        $draft = Article::factory()->create();

        $this->withToken($token)->postJson('/api/comments', ['article_id' => $foreign->id, 'author_name' => 'A', 'body' => 'Hello'])->assertJsonValidationErrors('article_id');
        $this->withToken($token)->postJson('/api/comments', ['article_id' => $draft->id, 'author_name' => 'A', 'body' => 'Hello'])->assertJsonValidationErrors('article_id');
    }

    public function test_moderation_emits_visibility_changes(): void
    {
        [$owner, $workspace, $blog] = $this->tenant();
        $article = Article::factory()->for($blog)->create();
        $comment = Comment::factory()->for($article)->create(['blog_id' => $blog->id]);
        $base = "/{$workspace->slug}/{$blog->slug}/comments";

        $this->actingAs($owner)->post("{$base}/{$comment->id}/moderate", ['status' => 'approved'])->assertSessionHasNoErrors();
        $this->actingAs($owner)->post("{$base}/{$comment->id}/reply", ['body' => 'Thanks!'])->assertSessionHasNoErrors();
        $this->actingAs($owner)->post("{$base}/{$comment->id}/moderate", ['status' => 'spam']);
        $this->actingAs($owner)->delete("{$base}/{$comment->id}");
        $this->actingAs($owner)->post("{$base}/{$comment->id}/restore");

        $this->assertSame(['comment.approved', 'comment.approved', 'comment.removed'], $this->events($blog));
        $reply = Comment::query()->whereNotNull('parent_id')->sole();
        $this->assertSame($blog->name, $reply->author_name);
        $this->assertSame(CommentStatus::Spam, $comment->refresh()->status);
        $this->assertFalse($comment->is_public);
    }

    public function test_bulk_moderation_and_permissions(): void
    {
        [$owner, $workspace, $blog] = $this->tenant();
        $author = $this->member($workspace, WorkspaceRole::Member, $blog, 'author');
        $article = Article::factory()->for($blog)->create();
        $comments = Comment::factory()->for($article)->count(3)->create(['blog_id' => $blog->id]);
        $base = "/{$workspace->slug}/{$blog->slug}/comments";

        $this->actingAs($author)->get($base)->assertForbidden();
        $this->actingAs($owner)->get($base)->assertInertia(fn ($page) => $page->component('content/comments')->has('comments.data', 3));

        $this->actingAs($owner)->post("{$base}/bulk", ['action' => 'approved', 'ids' => $comments->pluck('id')->all()])->assertSessionHasNoErrors();
        $this->assertSame(3, Comment::query()->where('is_public', true)->count());
    }

    public function test_dashboard_lists_what_needs_attention(): void
    {
        [$owner, $workspace, $blog] = $this->tenant();
        app(ArticleEditor::class)->create($blog, $owner, 'Mine');
        Comment::factory()->for(Article::factory()->for($blog)->create())->create(['blog_id' => $blog->id]);

        $this->actingAs($owner)->get("/{$workspace->slug}/{$blog->slug}")
            ->assertInertia(fn ($page) => $page->where('stats.pending_comments', 1)->has('myDrafts', 1)->where('myDrafts.0.title', 'Mine'));
    }
}
