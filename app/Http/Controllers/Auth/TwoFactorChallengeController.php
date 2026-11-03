<?php

namespace App\Http\Controllers\Auth;

use App\Auth\Passkeys;
use App\Auth\PendingLogin;
use App\Auth\TrustedDevices;
use App\Auth\TwoFactorAuthenticator;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The second login step: a code from the authenticator app, a recovery code or a passkey.
 */
class TwoFactorChallengeController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    public function __construct(
        private readonly PendingLogin $login,
        private readonly TrustedDevices $devices,
    ) {}

    public function create(Request $request): Response|RedirectResponse
    {
        $user = $this->login->user($request);

        if ($user === null) {
            return to_route('login');
        }

        return Inertia::render('auth/two-factor-challenge', [
            'totp' => $user->hasTotpEnabled(),
            'passkey' => $user->webauthnCredentials()->exists(),
        ]);
    }

    public function store(Request $request, TwoFactorAuthenticator $twoFactor, AuditLogger $audit): RedirectResponse
    {
        $user = $this->pendingUser($request);
        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:16'],
            'recovery_code' => ['nullable', 'string', 'max:32'],
            'trust' => ['boolean'],
        ]);

        $this->ensureIsNotRateLimited($user);

        $code = (string) ($validated['code'] ?? '');
        $recovery = (string) ($validated['recovery_code'] ?? '');

        $passed = $user->hasTotpEnabled() && ($code !== ''
            ? $twoFactor->verify($user, $code)
            : ($recovery !== '' && $twoFactor->useRecoveryCode($user, $recovery)));

        if (! $passed) {
            RateLimiter::hit($this->throttleKey($user));

            throw ValidationException::withMessages([$code !== '' ? 'code' : 'recovery_code' => __('That code is not valid.')]);
        }

        RateLimiter::clear($this->throttleKey($user));

        if ($recovery !== '' && $code === '') {
            $audit->record('auth.recovery_code_used', $user, ['remaining' => count($user->two_factor_recovery_codes ?? [])], actor: $user);
        }

        return $this->finish($request, $user, (bool) ($validated['trust'] ?? false));
    }

    public function passkeyOptions(Request $request, Passkeys $passkeys): JsonResponse
    {
        return response()->json($passkeys->assertionOptions($request, $this->pendingUser($request)));
    }

    public function passkey(Request $request, Passkeys $passkeys): JsonResponse
    {
        $user = $this->pendingUser($request);
        $validated = $request->validate(['credential' => ['required', 'array'], 'trust' => ['boolean']]);

        $this->ensureIsNotRateLimited($user);

        try {
            $passkeys->verify((array) $validated['credential'], $request, $user);
        } catch (ValidationException $exception) {
            RateLimiter::hit($this->throttleKey($user));

            throw $exception;
        }

        $redirect = $this->finish($request, $user, (bool) ($validated['trust'] ?? false));

        return response()->json(['redirect' => $redirect->getTargetUrl()]);
    }

    private function finish(Request $request, User $user, bool $trust): RedirectResponse
    {
        $this->login->complete($request, $user);

        if ($trust) {
            $this->devices->trust($user, $request);
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function pendingUser(Request $request): User
    {
        $user = $this->login->user($request);

        if ($user === null) {
            throw ValidationException::withMessages(['code' => __('Your login expired. Sign in again.')]);
        }

        return $user;
    }

    private function ensureIsNotRateLimited(User $user): void
    {
        if (RateLimiter::tooManyAttempts($this->throttleKey($user), self::MAX_ATTEMPTS)) {
            throw ValidationException::withMessages(['code' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($this->throttleKey($user))])]);
        }
    }

    private function throttleKey(User $user): string
    {
        return 'two-factor:'.$user->id;
    }
}
