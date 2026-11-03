<?php

namespace App\Auth;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * TOTP (RFC 6238) with an authenticator app, plus single-use recovery codes. The secret and the
 * codes are stored encrypted on the user (see the casts on User).
 */
class TwoFactorAuthenticator
{
    private const RECOVERY_CODES = 8;

    public function __construct(private readonly Google2FA $google2fa) {}

    public function newSecret(): string
    {
        return $this->google2fa->generateSecretKey(32);
    }

    public function qrCodeSvg(User $user, string $secret): string
    {
        $url = $this->google2fa->getQRCodeUrl((string) config('app.name'), $user->email, $secret);

        return (new Writer(new ImageRenderer(new RendererStyle(192, 1), new SvgImageBackEnd)))->writeString($url);
    }

    /**
     * Check a 6-digit code against the user's secret. A code is accepted once: replaying it, or
     * an older one, fails.
     */
    public function verify(User $user, string $code, ?string $secret = null): bool
    {
        $secret ??= $user->two_factor_secret;
        $code = preg_replace('/\s+/', '', $code) ?? '';

        if ($secret === null || preg_match('/^\d{6}$/', $code) !== 1) {
            return false;
        }

        $key = "two-factor-timestamp:{$user->id}";
        $last = Cache::get($key);
        $timestamp = $this->google2fa->verifyKeyNewer($secret, $code, is_int($last) ? $last : null, 1);

        if ($timestamp === false) {
            return false;
        }

        Cache::put($key, $timestamp === true ? $this->google2fa->getTimestamp() : $timestamp, now()->addMinutes(5));

        return true;
    }

    /**
     * @return list<string>
     */
    public function newRecoveryCodes(): array
    {
        return array_map(
            fn () => Str::lower(Str::random(5).'-'.Str::random(5)),
            range(1, self::RECOVERY_CODES),
        );
    }

    /**
     * Use up a recovery code. Returns false when it isn't one of the user's codes.
     */
    public function useRecoveryCode(User $user, string $code): bool
    {
        $code = Str::lower(trim($code));
        $codes = $user->two_factor_recovery_codes ?? [];

        foreach ($codes as $index => $candidate) {
            if (hash_equals($candidate, $code)) {
                unset($codes[$index]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

                return true;
            }
        }

        return false;
    }
}
