<?php

namespace App\Support;

class UserAgent
{
    /**
     * A rough "browser · platform" label, enough to recognise one's own devices.
     *
     * @return array{browser: string|null, platform: string|null, mobile: bool}
     */
    public static function parse(?string $userAgent): array
    {
        $ua = (string) $userAgent;

        $browser = match (true) {
            str_contains($ua, 'Edg/') => 'Edge',
            str_contains($ua, 'OPR/') => 'Opera',
            str_contains($ua, 'Firefox/') => 'Firefox',
            str_contains($ua, 'Chrome/') => 'Chrome',
            str_contains($ua, 'Safari/') => 'Safari',
            default => null,
        };

        $platform = match (true) {
            str_contains($ua, 'iPhone'), str_contains($ua, 'iPad') => 'iOS',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Mac OS X') => 'macOS',
            str_contains($ua, 'Linux') => 'Linux',
            default => null,
        };

        return [
            'browser' => $browser,
            'platform' => $platform,
            'mobile' => (bool) preg_match('/Mobile|Android|iPhone/', $ua),
        ];
    }
}
