<?php

namespace Tests\Feature\Security;

use App\Auth\TrustedDevices;
use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private function code(string $secret, int $offset = 0): string
    {
        $google2fa = new Google2FA;

        return $google2fa->oathTotp($secret, $google2fa->getTimestamp() + $offset);
    }

    private function userWithTotp(): User
    {
        return User::factory()->create([
            'two_factor_secret' => 'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP',
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => ['aaaaa-bbbbb', 'ccccc-ddddd'],
        ]);
    }

    private function login(User $user): void
    {
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/two-factor-challenge');
        $this->assertGuest();
    }

    public function test_setup_needs_a_confirmed_code_and_shows_recovery_codes_once(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);

        $this->post('/account/security/two-factor')->assertRedirect();
        $secret = $user->refresh()->two_factor_secret;
        $this->assertNotNull($secret);
        $this->assertFalse($user->hasTotpEnabled());
        $this->assertNotSame($secret, $user->getRawOriginal('two_factor_secret'), 'stored encrypted');

        $this->get('/account/security')->assertInertia(fn ($page) => $page->component('settings/security')->where('totp.setup.secret', $secret));

        $this->post('/account/security/two-factor/confirm', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->post('/account/security/two-factor/confirm', ['code' => $this->code($secret)])
            ->assertSessionHas('recovery_codes', fn (array $codes) => count($codes) === 8);

        $this->assertTrue($user->refresh()->hasTotpEnabled());
        $this->assertCount(8, $user->two_factor_recovery_codes ?? []);
    }

    public function test_security_page_asks_for_the_password_first(): void
    {
        $this->actingAs(User::factory()->create())->get('/account/security')->assertRedirect('/confirm-password');
    }

    public function test_login_asks_for_the_code_and_rejects_replays(): void
    {
        $user = $this->userWithTotp();
        $this->login($user);

        $this->get('/two-factor-challenge')->assertInertia(fn ($page) => $page->component('auth/two-factor-challenge')->where('totp', true)->where('passkey', false));

        $code = $this->code((string) $user->two_factor_secret);
        $this->post('/two-factor-challenge', ['code' => '123456'])->assertSessionHasErrors('code');
        $this->post('/two-factor-challenge', ['code' => $code])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);

        // The same code can't be used again.
        $this->post('/logout');
        $this->login($user);
        $this->post('/two-factor-challenge', ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_recovery_codes_work_once(): void
    {
        $user = $this->userWithTotp();
        $this->login($user);

        $this->post('/two-factor-challenge', ['recovery_code' => 'AAAAA-BBBBB'])->assertRedirect('/dashboard');
        $this->assertSame(['ccccc-ddddd'], $user->refresh()->two_factor_recovery_codes);

        $this->post('/logout');
        $this->login($user);
        $this->post('/two-factor-challenge', ['recovery_code' => 'aaaaa-bbbbb'])->assertSessionHasErrors('recovery_code');
    }

    public function test_challenge_is_rate_limited(): void
    {
        $user = $this->userWithTotp();
        $this->login($user);

        foreach (range(1, 5) as $attempt) {
            $this->post('/two-factor-challenge', ['code' => '000000']);
        }

        $this->post('/two-factor-challenge', ['code' => $this->code((string) $user->two_factor_secret)])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_a_trusted_device_skips_the_challenge_until_revoked(): void
    {
        $user = $this->userWithTotp();
        $this->login($user);

        $response = $this->post('/two-factor-challenge', ['code' => $this->code((string) $user->two_factor_secret), 'trust' => true]);
        $cookie = $response->getCookie(TrustedDevices::COOKIE);
        $this->assertNotNull($cookie);
        $device = TrustedDevice::query()->sole();
        $this->assertSame(hash('sha256', (string) $cookie->getValue()), $device->token_hash);

        $this->post('/logout');
        $this->withCookie(TrustedDevices::COOKIE, (string) $cookie->getValue())
            ->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);

        $this->withSession(['auth.password_confirmed_at' => time()])->delete("/account/security/devices/{$device->id}")->assertRedirect();
        $this->post('/logout');
        $this->withCookie(TrustedDevices::COOKIE, (string) $cookie->getValue())
            ->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/two-factor-challenge');
    }

    public function test_users_without_two_factor_log_in_directly(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_disabling_removes_the_secret_and_trusted_devices(): void
    {
        $user = $this->userWithTotp();
        TrustedDevice::factory()->for($user)->create();

        $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])->delete('/account/security/two-factor')->assertRedirect();

        $user->refresh();
        $this->assertFalse($user->hasTwoFactorEnabled());
        $this->assertNull($user->two_factor_recovery_codes);
        $this->assertSame(0, TrustedDevice::query()->count());
    }
}
