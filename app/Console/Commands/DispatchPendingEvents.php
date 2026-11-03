<?php

namespace App\Console\Commands;

use App\Jobs\DispatchEvents;
use App\Models\EventTransport;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('events:dispatch')]
#[Description('Queue delivery for every enabled transport that is behind its blog')]
class DispatchPendingEvents extends Command
{
    public function handle(): int
    {
        $queued = 0;

        EventTransport::query()
            ->where('enabled', true)
            ->whereHas('blog', fn ($query) => $query->active()->whereColumn('blogs.event_sequence', '>', 'event_transports.last_dispatched_sequence'))
            ->each(function (EventTransport $transport) use (&$queued) {
                DispatchEvents::dispatch($transport);
                $queued++;
            });

        $this->info("Queued {$queued} transport(s).");

        return self::SUCCESS;
    }
}
