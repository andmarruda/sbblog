<?php

namespace App\Publishing;

use App\Models\Blog;
use App\Models\OutboxEvent;
use App\Publishing\Events\PublishableEvent;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Appends to a blog's event log (ERD rule 5). Must run inside the same transaction as the
 * content write: the blog row is locked to take the next sequence number, which keeps the
 * log gapless and ordered per blog, and a rollback discards the event with the write.
 */
class EventRecorder
{
    public function record(Blog $blog, PublishableEvent $event): OutboxEvent
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Publishing events must be recorded inside a database transaction.');
        }

        $sequence = (int) DB::table('blogs')->where('id', $blog->id)->lockForUpdate()->value('event_sequence') + 1;

        DB::table('blogs')->where('id', $blog->id)->update(['event_sequence' => $sequence]);
        $blog->forceFill(['event_sequence' => $sequence])->syncOriginalAttribute('event_sequence');

        return OutboxEvent::query()->create([
            'blog_id' => $blog->id,
            'sequence' => $sequence,
            'type' => $event::type(),
            'aggregate_type' => $event->aggregateType(),
            'aggregate_id' => $event->aggregateId(),
            'schema_version' => $event::schemaVersion(),
            'payload' => $event->payload(),
            'occurred_at' => now(),
        ]);
    }
}
