<?php

namespace App\Console\Commands;

use App\Models\Blog;
use App\Models\Snapshot;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

#[Signature('events:prune {--days= : Keep events newer than this many days}')]
#[Description('Delete old events already covered by a snapshot and delivered to every transport')]
class PruneEvents extends Command
{
    /** Older snapshots kept per blog, besides the latest. */
    private const KEEP_SNAPSHOTS = 2;

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('sbblog.publishing.event_retention_days'));
        $deleted = 0;

        Blog::query()->each(function (Blog $blog) use ($days, &$deleted) {
            $snapshot = Snapshot::query()->whereBelongsTo($blog)->latest('up_to_sequence')->first();

            if ($snapshot === null) {
                return;
            }

            // Never drop what a push transport still has to deliver.
            $undelivered = DB::table('event_transports')->where('blog_id', $blog->id)->where('enabled', true)->min('last_dispatched_sequence');
            $limit = min($snapshot->up_to_sequence, $undelivered === null ? PHP_INT_MAX : (int) $undelivered);

            $deleted += DB::table('outbox_events')
                ->where('blog_id', $blog->id)
                ->where('sequence', '<=', $limit)
                ->where('occurred_at', '<', now()->subDays($days))
                ->delete();

            Snapshot::query()->whereBelongsTo($blog)->latest('up_to_sequence')->skip(self::KEEP_SNAPSHOTS + 1)->take(PHP_INT_MAX)->get()
                ->each(function (Snapshot $old) {
                    Storage::disk('local')->delete($old->storage_path);
                    $old->delete();
                });
        });

        $this->info("Deleted {$deleted} event(s).");

        return self::SUCCESS;
    }
}
