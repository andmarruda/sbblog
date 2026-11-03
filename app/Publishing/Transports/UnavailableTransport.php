<?php

namespace App\Publishing\Transports;

use App\Models\EventTransport;
use RuntimeException;

/**
 * Kafka, NATS and Redis need client libraries this installation doesn't ship yet.
 */
class UnavailableTransport implements Transport
{
    public function send(EventTransport $transport, array $envelopes): void
    {
        throw new RuntimeException("The {$transport->driver->value} driver is not available in this installation yet.");
    }
}
