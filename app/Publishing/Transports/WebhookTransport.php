<?php

namespace App\Publishing\Transports;

use App\Models\EventTransport;
use App\Support\SafeUrl;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * POSTs `{"events": [...]}` as JSON. `X-SBBlog-Signature: sha256=<hex>` is the HMAC of the raw body
 * with the transport secret; receivers should verify it and answer 2xx.
 */
class WebhookTransport implements Transport
{
    public function send(EventTransport $transport, array $envelopes): void
    {
        $config = $transport->config ?? [];

        if (! SafeUrl::isPublic((string) ($config['url'] ?? ''))) {
            throw new RuntimeException('The webhook URL does not resolve to a public address.');
        }

        $body = (string) json_encode(['events' => $envelopes], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $response = Http::timeout(10)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'User-Agent' => 'SBBlog-Webhook/2',
                'X-SBBlog-Blog' => $transport->blog_id,
                'X-SBBlog-Signature' => 'sha256='.hash_hmac('sha256', $body, (string) ($config['secret'] ?? '')),
            ])
            ->withBody($body, 'application/json')
            ->post((string) ($config['url'] ?? ''));

        if (! $response->successful()) {
            throw new RuntimeException("Webhook answered HTTP {$response->status()}.");
        }
    }
}
