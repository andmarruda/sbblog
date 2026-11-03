<?php

namespace App\Console\Commands;

use App\Jobs\BuildSnapshot;
use App\Models\Blog;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('snapshots:build')]
#[Description('Queue a snapshot for every blog with a frontend and new events since its last snapshot')]
class BuildSnapshots extends Command
{
    public function handle(): int
    {
        $queued = 0;

        Blog::query()
            ->active()
            ->whereHas('frontends')
            ->where('event_sequence', '>', fn ($query) => $query->selectRaw('coalesce(max(up_to_sequence), 0)')->from('snapshots')->whereColumn('snapshots.blog_id', 'blogs.id'))
            ->each(function (Blog $blog) use (&$queued) {
                BuildSnapshot::dispatch($blog);
                $queued++;
            });

        $this->info("Queued {$queued} snapshot(s).");

        return self::SUCCESS;
    }
}
