<?php

namespace App\Http\Controllers\Settings;

use App\Auth\Passkeys;
use App\Auth\TrustedDevices;
use App\Auth\TwoFactorAuthenticator;
use App\Http\Controllers\Controller;
use App\Models\TrustedDevice;
use App\Models\User;
use App\Models\WebauthnCredential;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Account security: authenticator app, recovery codes, passkeys and trusted devices. The whole
 * page sits behind a recent password confirmation.
 */
class SecurityController extends Controller
{
    public function __construct(
        private readonly TwoFactorAuthenticator $twoFactor,
        private readonly TrustedDevices $devices,
        private readonly AuditLogger $audit,
    ) {}

    public function edit(Request $request): Response
    {
        $user = $this->user($request);
        $pending = ! $user->hasTotpEnabled() && $user->two_factor_secret !== null;
        $current = $this->devices->current($user, $request);

        return Inertia::render('settings/security', [
            'totp' => [
                'enabled' => $user->hasTotpEnabled(),
                'setup' => $pending ? [
                    'qr_svg' => $this->twoFactor->qrCodeSvg($user, (string) $user->two_factor_secret),
                    'secret' => $user->two_factor_secret,
                ] : null,
                'recovery_codes_left' => count($user->two_factor_recovery_codes ?? []),
            ],
            'recoveryCodes' => $request->session()->get('recovery_codes'),
            'passkeys' => $user->webauthnCredentials()->latest()->get()->map(fn (WebauthnCredential $passkey) => [
                'id' => $passkey->id,
                'name' => $passkey->name,
                'created_at' => $passkey->created_at?->toIso8601String(),
                'last_used_at' => $passkey->last_used_at?->toIso8601String(),
            ]),
            'devices' => $user->trustedDevices()->where('expires_at', '>', now())->latest()->get()->map(fn (TrustedDevice $device) => [
                'id' => $device->id,
                'label' => $device->label,
                'current' => $device->is($current),
                'created_at' => $device->created_at?->toIso8601String(),
                'expires_at' => $device->expires_at->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Start setup: a new secret, not active until a code confirms it.
     */
    public function enableTotp(Request $request): RedirectResponse
    {
        $user = $this->user($request);

        if (! $user->hasTotpEnabled()) {
            $user->forceFill(['two_factor_secret' => $this->twoFactor->newSecret(), 'two_factor_confirmed_at' => null])->save();
        }

        return back();
    }

    public function confirmTotp(Request $request): RedirectResponse
    {
        $user = $this->user($request);
        $validated = $request->validate(['code' => ['required', 'string', 'max:16']]);

        if ($user->hasTotpEnabled() || $user->two_factor_secret === null || ! $this->twoFactor->verify($user, (string) $validated['code'])) {
            throw ValidationException::withMessages(['code' => __('That code is not valid.')]);
        }

        $codes = $this->twoFactor->newRecoveryCodes();
        $user->forceFill(['two_factor_confirmed_at' => now(), 'two_factor_recovery_codes' => $codes])->save();
        $this->audit->record('auth.two_factor_enabled', $user, actor: $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Two-factor authentication is on.')]);

        return back()->with('recovery_codes', $codes);
    }

    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $user = $this->user($request);
        abort_unless($user->hasTotpEnabled(), 404);

        $codes = $this->twoFactor->newRecoveryCodes();
        $user->forceFill(['two_factor_recovery_codes' => $codes])->save();
        $this->audit->record('auth.recovery_codes_regenerated', $user, actor: $user);

        return back()->with('recovery_codes', $codes);
    }

    public function disableTotp(Request $request): RedirectResponse
    {
        $user = $this->user($request);
        $user->forceFill(['two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null])->save();

        if (! $user->webauthnCredentials()->exists()) {
            $this->devices->revokeAll($user);
        }

        $this->audit->record('auth.two_factor_disabled', $user, actor: $user);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Authenticator app removed.')]);

        return back();
    }

    public function passkeyOptions(Request $request, Passkeys $passkeys): JsonResponse
    {
        return response()->json($passkeys->registrationOptions($this->user($request), $request));
    }

    public function storePasskey(Request $request, Passkeys $passkeys): JsonResponse
    {
        $user = $this->user($request);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:64'],
            'credential' => ['required', 'array'],
        ]);

        $passkey = $passkeys->register($user, (string) $validated['name'], (array) $validated['credential'], $request);
        $this->audit->record('auth.passkey_added', $user, ['name' => $passkey->name], actor: $user);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Passkey added.')]);

        return response()->json(['id' => $passkey->id], 201);
    }

    public function destroyPasskey(Request $request, WebauthnCredential $passkey): RedirectResponse
    {
        $user = $this->user($request);
        abort_unless($passkey->user_id === $user->id, 404);

        $passkey->delete();

        if (! $user->hasTotpEnabled() && ! $user->webauthnCredentials()->exists()) {
            $this->devices->revokeAll($user);
        }

        $this->audit->record('auth.passkey_removed', $user, ['name' => $passkey->name], actor: $user);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Passkey removed.')]);

        return back();
    }

    public function destroyDevice(Request $request, TrustedDevice $device): RedirectResponse
    {
        abort_unless($device->user_id === $this->user($request)->id, 404);

        $device->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Device removed.')]);

        return back();
    }

    public function destroyDevices(Request $request): RedirectResponse
    {
        $this->devices->revokeAll($this->user($request));
        Inertia::flash('toast', ['type' => 'success', 'message' => __('All devices removed.')]);

        return back();
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
