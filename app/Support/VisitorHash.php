<?php

namespace App\Support;

/**
 * Hashes for readers (ERD rule 7): the IP is keyed with a salt that changes every day, so the
 * same reader can be rate limited today but not tracked across days.
 */
class VisitorHash
{
    public static function ip(?string $ip): ?string
    {
        return $ip === null ? null : hash_hmac('sha256', $ip, config('app.key').'|'.now()->toDateString());
    }

    public static function email(?string $email): ?string
    {
        return $email === null || $email === '' ? null : hash('sha256', mb_strtolower(trim($email)));
    }
}
