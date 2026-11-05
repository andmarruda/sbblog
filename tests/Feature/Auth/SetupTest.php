<?php

namespace Tests\Feature\Auth;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_fresh_install_sends_guests_to_setup(): void
    {
        $this->get('/login')->assertRedirect('/setup');
        $this->get('/register')->assertRedirect('/setup');
        $this->get('/setup')->assertOk()->assertInertia(fn (Assert $page) => $page->component('auth/setup'));
    }

    public function test_setup_creates_a_verified_administrator_and_logs_in(): void
    {
        $this->post('/setup', [
            'name' => 'Ana',
            'email' => ' Ana@Example.com ',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/onboarding');

        $user = User::query()->sole();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('ana@example.com', $user->email);
        $this->assertTrue($user->is_admin);
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertTrue(AuditLog::query()->where('action', 'instance.setup')->where('actor_user_id', $user->id)->exists());
    }

    public function test_setup_is_closed_once_an_account_exists(): void
    {
        User::factory()->create();

        $this->get('/setup')->assertNotFound();
        $this->post('/setup', [
            'name' => 'Mallory',
            'email' => 'mallory@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'mallory@example.com']);
        $this->get('/login')->assertOk();
    }

    public function test_setup_stays_closed_when_the_only_account_was_deleted(): void
    {
        User::factory()->create()->delete();

        $this->get('/setup')->assertNotFound();
    }

    public function test_setup_validates_the_form(): void
    {
        $this->post('/setup', [
            'name' => '',
            'email' => 'not-an-email',
            'password' => 'password',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors(['name', 'email', 'password']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_the_admin_flag_is_not_mass_assignable(): void
    {
        $user = User::query()->create([
            'name' => 'Ana',
            'email' => 'ana@example.com',
            'password' => 'password',
            'is_admin' => true,
        ]);

        $this->assertFalse($user->refresh()->is_admin);
    }
}
