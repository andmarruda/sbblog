<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Frontend sync tokens: shown once, stored as a SHA-256 hash.
 */
class SyncToken
{
    public const PREFIX = 'sbf_';

    public static function generate(): string
    {
        return self::PREFIX.Str::random(48);
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
