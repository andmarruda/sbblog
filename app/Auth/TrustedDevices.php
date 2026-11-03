<?php

namespace App\Auth;

use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * "Trust this device for 30 days": skips the second step at login. The cookie holds a random
 * token; only its hash is stored, and the user can revoke devices from account settings.
 */
class TrustedDevices
{
    public const COOKIE = 'sbblog_trusted_device';

    private const DAYS = 30;

    public function trust(User $user, Request $request): TrustedDevice
    {
        $token = Str::random(64);

        $device = $user->trustedDevices()->create([
            'token_hash' => hash('sha256', $token),
            'label' => self::label((string) $request->userAgent()),
            'expires_at' => now()->addDays(self::DAYS),
        ]);

        Cookie::queue(Cookie::make(self::COOKIE, $token, self::DAYS * 24 * 60, httpOnly: true, sameSite: 'lax'));

        return $device;
    }

    public function current(User $user, Request $request): ?TrustedDevice
    {
        $token = $request->cookie(self::COOKIE);

        if (! is_string($token) || $token === '') {
            return null;
        }

        return $user->trustedDevices()
            ->where('token_hash', hash('sha256', $token))
            ->where('expires_at', '>', now())
            ->first();
    }

    public function isTrusted(User $user, Request $request): bool
    {
        return $this->current($user, $request) !== null;
    }

    public function revokeAll(User $user): void
    {
        $user->trustedDevices()->delete();
    }

    /**
     * "Firefox on Linux", good enough for a list of devices.
     */
    public static function label(string $userAgent): string
    {
        $browser = match (true) {
            str_contains($userAgent, 'Edg/') => 'Edge',
            str_contains($userAgent, 'OPR/') => 'Opera',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Chrome/') => 'Chrome',
            str_contains($userAgent, 'Safari/') => 'Safari',
            default => 'Browser',
        };

        $platform = match (true) {
            str_contains($userAgent, 'iPhone'), str_contains($userAgent, 'iPad') => 'iOS',
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'Mac OS') => 'macOS',
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => null,
        };

        return $platform === null ? $browser : "{$browser} · {$platform}";
    }
}
