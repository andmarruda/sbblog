<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Hashes for readers (ERD rule 7): the IP is keyed with a salt that changes every day, so the
 * same reader can be rate limited today but not tracked across days.
 */
class VisitorHash
{
    /**
     * @param  CarbonInterface|null  $on  The day whose salt to use (imports hash old IPs with their own day).
     */
    public static function ip(?string $ip, ?CarbonInterface $on = null): ?string
    {
        return $ip === null ? null : hash_hmac('sha256', $ip, config('app.key').'|'.($on ?? now())->toDateString());
    }

    public static function email(?string $email): ?string
    {
        return $email === null || $email === '' ? null : hash('sha256', mb_strtolower(trim($email)));
    }
}
