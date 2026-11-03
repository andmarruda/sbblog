<?php

namespace App\Support;

class IpHash
{
    /**
     * Keyed hash of an IP address. Stable, so repeated events from one address can be correlated,
     * but not reversible without the app key.
     */
    public static function make(?string $ip): ?string
    {
        return $ip === null ? null : hash_hmac('sha256', $ip, (string) config('app.key'));
    }
}
