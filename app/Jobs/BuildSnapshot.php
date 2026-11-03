<?php

namespace App\Jobs;

use App\Enums\ArticleStatus;
use App\Enums\CommentStatus;
use App\Enums\IntegrationScope;
use App\Models\Article;
use App\Models\AuthorProfile;
use App\Models\Blog;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Integration;
use App\Models\Snapshot;
use App\Models\Tag;
use App\Publishing\Events;
use App\Publishing\Events\PublishableEvent;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * The whole live state of a blog as a list of events (resources/schemas/events/snapshot.schema.json).
 * New frontends apply it, then pull events after `up_to_sequence`.
 */
class BuildSnapshot implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /** The model may be gone by the time the job runs; then there's nothing to do. */
    public bool $deleteWhenMissingModels = true;

    public function __construct(public readonly Blog $blog) {}

    public function uniqueId(): string
    {
        return $this->blog->id;
    }

    public function handle(): void
    {
        [$upTo, $events] = DB::transaction(function () {
            // One consistent view of the data and the sequence, without blocking writers. Only
            // possible when this job owns the transaction (not inside a caller's savepoint).
            if (DB::transactionLevel() === 1) {
                DB::statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
            }

            $blog = Blog::query()->findOrFail($this->blog->id);

            return [$blog->event_sequence, $this->events($blog)];
        });

        $json = (string) json_encode([
            'blog_id' => $this->blog->id,
            'up_to_sequence' => $upTo,
            'created_at' => now()->toIso8601String(),
            'events' => array_map(fn (PublishableEvent $event) => [
                'type' => $event::type(),
                'schema_version' => $event::schemaVersion(),
                'aggregate_type' => $event->aggregateType(),
                'aggregate_id' => $event->aggregateId(),
                'payload' => $event->payload(),
            ], $events),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $path = "snapshots/{$this->blog->id}/".Str::uuid7().'.json.gz';
        $gzipped = (string) gzencode($json, 6);
        Storage::disk('local')->put($path, $gzipped);

        Snapshot::query()->create([
            'blog_id' => $this->blog->id,
            'up_to_sequence' => $upTo,
            'storage_path' => $path,
            'size_bytes' => strlen($gzipped),
        ]);
    }

    /**
     * @return list<PublishableEvent>
     */
    private function events(Blog $blog): array
    {
        $events = [new Events\BlogUpdated($blog)];

        Category::query()->whereBelongsTo($blog)->orderBy('position')->each(function (Category $category) use (&$events) {
            $events[] = new Events\CategoryUpserted($category);
        });
        Tag::query()->whereBelongsTo($blog)->each(function (Tag $tag) use (&$events) {
            $events[] = new Events\TagUpserted($tag);
        });
        AuthorProfile::query()->whereBelongsTo($blog)->each(function (AuthorProfile $author) use (&$events) {
            $events[] = new Events\AuthorUpserted($author);
        });

        Article::query()
            ->whereBelongsTo($blog)
            ->where('status', ArticleStatus::Published)
            ->whereNotNull('published_revision_id')
            ->with(['publishedRevision', 'language', 'authorProfile', 'category', 'tags'])
            ->each(function (Article $article) use (&$events) {
                $events[] = new Events\ArticlePublished($article);
            });

        Integration::query()
            ->whereBelongsTo($blog)
            ->where('enabled', true)
            ->orderBy('created_at')
            ->get()
            ->groupBy(fn (Integration $integration) => $integration->scope->value.'|'.($integration->author_profile_id ?? $integration->article_id ?? ''))
            ->each(function ($group) use ($blog, &$events) {
                /** @var Integration $first */
                $first = $group->first();
                $target = $first->scope === IntegrationScope::Blog ? null : ($first->author_profile_id ?? $first->article_id);
                $events[] = new Events\IntegrationsUpdated($blog->id, $first->scope, $target, $group->values());
            });

        Comment::query()
            ->whereBelongsTo($blog)
            ->where('status', CommentStatus::Approved)
            ->where('is_public', true)
            ->whereHas('article', fn ($query) => $query->where('status', ArticleStatus::Published))
            ->orderBy('created_at')
            ->each(function (Comment $comment) use (&$events) {
                $events[] = new Events\CommentApproved($comment);
            });

        return $events;
    }
}
