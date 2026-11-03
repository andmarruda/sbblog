<?php

namespace App\Auth;

use App\Models\User;
use App\Models\WebauthnCredential;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use lbuchs\WebAuthn\WebAuthn;
use lbuchs\WebAuthn\WebAuthnException;
use stdClass;

/**
 * WebAuthn passkeys. Options go to the browser as JSON with base64url binaries (the format
 *
 * @simplewebauthn/browser expects); each challenge is kept in the session and used once.
 */
class Passkeys
{
    private const TIMEOUT = 300;

    public function registrationOptions(User $user, Request $request): stdClass
    {
        $webauthn = $this->webauthn();
        $options = $webauthn->getCreateArgs(
            $user->id,
            $user->email,
            $user->name,
            self::TIMEOUT,
            requireResidentKey: 'preferred',
            requireUserVerification: 'preferred',
            excludeCredentialIds: $user->webauthnCredentials()->pluck('credential_id')->map(fn (string $id) => self::decode($id))->all(),
        );

        $request->session()->put('webauthn.register', $webauthn->getChallenge()->getBinaryString());

        return $options->publicKey;
    }

    /**
     * @param  array<array-key, mixed>  $credential  The browser's RegistrationResponseJSON.
     */
    public function register(User $user, string $name, array $credential, Request $request): WebauthnCredential
    {
        $challenge = $request->session()->pull('webauthn.register');
        $response = (array) ($credential['response'] ?? []);

        try {
            if (! is_string($challenge)) {
                throw new WebAuthnException('no challenge');
            }

            $clientData = self::decode((string) ($response['clientDataJSON'] ?? ''));
            $this->ensureOrigin($clientData);

            $data = $this->webauthn()->processCreate(
                $clientData,
                self::decode((string) ($response['attestationObject'] ?? '')),
                $challenge,
                requireUserVerification: false,
                requireUserPresent: true,
                failIfRootMismatch: false,
            );
        } catch (WebAuthnException) {
            throw ValidationException::withMessages(['credential' => __('The passkey could not be verified. Try again.')]);
        }

        return $user->webauthnCredentials()->create([
            'name' => $name,
            'credential_id' => self::encode((string) $data->credentialId),
            'public_key' => (string) $data->credentialPublicKey,
            'sign_count' => (int) ($data->signatureCounter ?? 0),
        ]);
    }

    /**
     * Options to sign in. With a user, only their passkeys are offered (second step); without,
     * the browser lists the passkeys it holds for this site.
     */
    public function assertionOptions(Request $request, ?User $user = null): stdClass
    {
        $webauthn = $this->webauthn();
        $ids = $user === null ? [] : $user->webauthnCredentials()->pluck('credential_id')->map(fn (string $id) => self::decode($id))->all();

        $options = $webauthn->getGetArgs($ids, self::TIMEOUT, requireUserVerification: $user === null ? 'required' : 'preferred');
        $request->session()->put('webauthn.assert', $webauthn->getChallenge()->getBinaryString());

        return $options->publicKey;
    }

    /**
     * Verify a sign-in and return the credential used. Without a user (passwordless), the
     * authenticator must have verified the person (PIN or biometrics).
     *
     * @param  array<array-key, mixed>  $credential  The browser's AuthenticationResponseJSON.
     */
    public function verify(array $credential, Request $request, ?User $user = null): WebauthnCredential
    {
        $challenge = $request->session()->pull('webauthn.assert');
        $response = (array) ($credential['response'] ?? []);
        $fail = fn () => ValidationException::withMessages(['credential' => __('The passkey could not be verified. Try again.')]);

        $stored = WebauthnCredential::query()
            ->where('credential_id', (string) ($credential['id'] ?? ''))
            ->when($user, fn ($query, User $owner) => $query->whereBelongsTo($owner))
            ->first();

        if (! is_string($challenge) || $stored === null) {
            throw $fail();
        }

        $handle = $response['userHandle'] ?? null;
        if (is_string($handle) && $handle !== '' && ! hash_equals($stored->user_id, self::decode($handle))) {
            throw $fail();
        }

        try {
            $clientData = self::decode((string) ($response['clientDataJSON'] ?? ''));
            $this->ensureOrigin($clientData);

            $webauthn = $this->webauthn();
            $webauthn->processGet(
                $clientData,
                self::decode((string) ($response['authenticatorData'] ?? '')),
                self::decode((string) ($response['signature'] ?? '')),
                $stored->public_key,
                $challenge,
                $stored->sign_count > 0 ? $stored->sign_count : null,
                requireUserVerification: $user === null,
            );
        } catch (WebAuthnException) {
            throw $fail();
        }

        $stored->forceFill(['sign_count' => $webauthn->getSignatureCounter() ?? $stored->sign_count, 'last_used_at' => now()])->save();

        return $stored;
    }

    public static function encode(string $binary): string
    {
        return rtrim(strtr(base64_encode($binary), '+/', '-_'), '=');
    }

    public static function decode(string $base64url): string
    {
        return (string) base64_decode(strtr($base64url, '-_', '+/'), true);
    }

    private function webauthn(): WebAuthn
    {
        return new WebAuthn((string) config('app.name'), $this->rpId(), ['none'], true);
    }

    private function rpId(): string
    {
        return (string) parse_url((string) config('app.url'), PHP_URL_HOST);
    }

    /**
     * The library accepts any origin ending in the RP ID; require the console's exact origin.
     */
    private function ensureOrigin(string $clientDataJson): void
    {
        $origin = json_decode($clientDataJson, true)['origin'] ?? null;
        $url = parse_url((string) config('app.url'));
        $expected = ($url['scheme'] ?? 'https').'://'.($url['host'] ?? '').(isset($url['port']) ? ':'.$url['port'] : '');

        if ($origin !== $expected) {
            throw new WebAuthnException('origin mismatch', WebAuthnException::INVALID_ORIGIN);
        }
    }
}
