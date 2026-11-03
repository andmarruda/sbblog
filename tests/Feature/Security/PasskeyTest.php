<?php

namespace Tests\Feature\Security;

use App\Auth\Passkeys;
use App\Models\User;
use App\Models\WebauthnCredential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OpenSSLAsymmetricKey;
use Tests\TestCase;

/**
 * Signs real ES256 assertions, the way an authenticator does, against the console's challenge.
 */
class PasskeyTest extends TestCase
{
    use RefreshDatabase;

    private OpenSSLAsymmetricKey $key;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'http://localhost']);
        $key = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        $this->assertInstanceOf(OpenSSLAsymmetricKey::class, $key);
        $this->key = $key;
    }

    private function passkey(User $user): WebauthnCredential
    {
        return WebauthnCredential::factory()->for($user)->create([
            'credential_id' => Passkeys::encode(random_bytes(16)),
            'public_key' => (string) openssl_pkey_get_details($this->key)['key'],
            'sign_count' => 0,
        ]);
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function assertion(WebauthnCredential $passkey, array $options, string $origin = 'http://localhost', bool $verified = true, ?string $userHandle = null): array
    {
        $clientData = (string) json_encode(['type' => 'webauthn.get', 'challenge' => $options['challenge'], 'origin' => $origin]);
        $authData = hash('sha256', 'localhost', true).chr($verified ? 0x05 : 0x01).pack('N', 7);
        openssl_sign($authData.hash('sha256', $clientData, true), $signature, $this->key, OPENSSL_ALGO_SHA256);

        return [
            'id' => $passkey->credential_id,
            'rawId' => $passkey->credential_id,
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => Passkeys::encode($clientData),
                'authenticatorData' => Passkeys::encode($authData),
                'signature' => Passkeys::encode($signature),
                'userHandle' => Passkeys::encode($userHandle ?? $passkey->user_id),
            ],
        ];
    }

    public function test_passwordless_login_with_a_passkey(): void
    {
        $user = User::factory()->create();
        $passkey = $this->passkey($user);

        $options = $this->postJson('/passkeys/login/options')->assertOk()->json();
        $this->assertSame([], $options['allowCredentials'] ?? []);
        $this->assertSame('required', $options['userVerification']);

        $this->postJson('/passkeys/login', ['credential' => $this->assertion($passkey, $options)])
            ->assertOk()->assertJsonPath('redirect', url('/dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame(7, $passkey->refresh()->sign_count);
        $this->assertNotNull($passkey->last_used_at);
    }

    public function test_challenges_are_single_use_and_bound_to_the_origin(): void
    {
        $user = User::factory()->create();
        $passkey = $this->passkey($user);

        $options = $this->postJson('/passkeys/login/options')->json();
        $this->postJson('/passkeys/login', ['credential' => $this->assertion($passkey, $options, 'http://localhost.evil.test')])->assertUnprocessable();
        $this->postJson('/passkeys/login', ['credential' => $this->assertion($passkey, $options)])->assertUnprocessable();

        $options = $this->postJson('/passkeys/login/options')->json();
        $this->postJson('/passkeys/login', ['credential' => $this->assertion($passkey, $options, verified: false)])->assertUnprocessable();

        $options = $this->postJson('/passkeys/login/options')->json();
        $this->postJson('/passkeys/login', ['credential' => $this->assertion($passkey, $options, userHandle: User::factory()->create()->id)])->assertUnprocessable();

        $this->assertGuest();
    }

    public function test_a_passkey_is_a_second_factor(): void
    {
        $user = User::factory()->create();
        $passkey = $this->passkey($user);
        $other = $this->passkey(User::factory()->create());

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/two-factor-challenge');

        $options = $this->postJson('/two-factor-challenge/passkey/options')->assertOk()->json();
        $this->assertSame([$passkey->credential_id], array_column($options['allowCredentials'], 'id'));

        // Another user's passkey can't finish this login.
        $this->postJson('/two-factor-challenge/passkey', ['credential' => $this->assertion($other, $options)])->assertUnprocessable();

        $options = $this->postJson('/two-factor-challenge/passkey/options')->json();
        $this->postJson('/two-factor-challenge/passkey', ['credential' => $this->assertion($passkey, $options, verified: false)])
            ->assertOk();

        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_options_exclude_existing_passkeys(): void
    {
        $user = User::factory()->create();
        $passkey = $this->passkey($user);

        $options = $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])
            ->postJson('/account/security/passkeys/options')->assertOk()->json();

        $this->assertSame('none', $options['attestation']);
        $this->assertSame([$passkey->credential_id], array_column($options['excludeCredentials'], 'id'));
        $this->assertSame(Passkeys::encode($user->id), $options['user']['id']);

        $this->postJson('/account/security/passkeys', ['name' => 'Laptop', 'credential' => ['response' => ['clientDataJSON' => 'x', 'attestationObject' => 'y']]])
            ->assertUnprocessable()->assertJsonValidationErrors('credential');
    }

    public function test_users_only_remove_their_own_passkeys(): void
    {
        $user = User::factory()->create();
        $foreign = $this->passkey(User::factory()->create());
        $own = $this->passkey($user);

        $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);
        $this->delete("/account/security/passkeys/{$foreign->id}")->assertNotFound();
        $this->delete("/account/security/passkeys/{$own->id}")->assertRedirect();

        $this->assertModelExists($foreign);
        $this->assertModelMissing($own);
    }
}
