<?php

namespace App\Publishing\Transports;

use App\Models\EventTransport;

/**
 * Pushes envelopes (resources/schemas/events/envelope.schema.json) somewhere. Must throw on failure
 * so the dispatcher keeps its position and retries.
 */
interface Transport
{
    /**
     * @param  list<array<string, mixed>>  $envelopes
     */
    public function send(EventTransport $transport, array $envelopes): void;
}
