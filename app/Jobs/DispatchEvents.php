<?php

namespace App\Jobs;

use App\Enums\TransportDriver;
use App\Models\EventTransport;
use App\Publishing\Transports\Transport;
use App\Publishing\Transports\UnavailableTransport;
use App\Publishing\Transports\WebhookTransport;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Delivers a transport's pending events in order, in batches. The position only moves after a
 * successful delivery; failures are recorded and retried on the next run.
 */
class DispatchEvents implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public const BATCH = 100;

    /** Batches per run, so one slow transport doesn't hold a worker forever. */
    public const MAX_BATCHES = 20;

    public function __construct(public readonly EventTransport $transport) {}

    public function uniqueId(): string
    {
        return $this->transport->id;
    }

    public static function driver(TransportDriver $driver): Transport
    {
        return match ($driver) {
            TransportDriver::Webhook => app(WebhookTransport::class),
            default => app(UnavailableTransport::class),
        };
    }

    public function handle(): void
    {
        $transport = $this->transport->fresh();

        if ($transport === null || ! $transport->enabled) {
            return;
        }

        for ($batch = 0; $batch < self::MAX_BATCHES; $batch++) {
            $rows = DB::table('outbox_events')
                ->where('blog_id', $transport->blog_id)
                ->where('sequence', '>', $transport->last_dispatched_sequence)
                ->orderBy('sequence')
                ->limit(self::BATCH)
                ->get(['sequence', 'type', 'schema_version', 'aggregate_type', 'aggregate_id', 'occurred_at', DB::raw('payload::text as payload')]);

            if ($rows->isEmpty()) {
                return;
            }

            $envelopes = $rows->map(fn (object $row) => [
                'sequence' => (int) $row->sequence,
                'type' => $row->type,
                'schema_version' => (int) $row->schema_version,
                'aggregate_type' => $row->aggregate_type,
                'aggregate_id' => $row->aggregate_id,
                'occurred_at' => now()->parse($row->occurred_at)->toIso8601String(),
                'payload' => json_decode((string) $row->payload),
            ])->values()->all();

            $envelopes = array_values($envelopes);

            try {
                self::driver($transport->driver)->send($transport, $envelopes);
            } catch (Throwable $exception) {
                $transport->forceFill(['last_error_at' => now(), 'last_error' => mb_substr($exception->getMessage(), 0, 1000)])->save();

                return;
            }

            $transport->forceFill([
                'last_dispatched_sequence' => (int) $rows->last()->sequence,
                'last_error' => null,
                'last_error_at' => null,
            ])->save();
        }
    }
}
