<?php

namespace App\Jobs;

use App\Models\Blog;
use App\Models\Snapshot;
use App\Publishing\BlogState;
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

            return [$blog->event_sequence, BlogState::events($blog)];
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
}
