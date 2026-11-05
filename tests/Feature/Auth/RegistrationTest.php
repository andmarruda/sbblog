<?php

namespace Tests\Feature\Auth;

use App\Models\Invitation;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Sign-up is invite-only: it opens from an invitation link, for that invitation's address.
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_without_an_invitation_is_closed(): void
    {
        User::factory()->create();

        $this->get('/register')->assertRedirect('/login');

        $this->post('/register', [
            'name' => 'Ana',
            'email' => 'ana@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'ana@example.com']);
    }

    public function test_invited_guests_can_register_with_the_invited_address(): void
    {
        Notification::fake();
        $this->seed(LanguageSeeder::class);
        Invitation::factory()->withToken('secret-token')->create(['email' => 'ana@example.com']);

        $this->get('/invitations/secret-token')->assertOk();
        $this->get('/login')->assertInertia(fn (Assert $page) => $page->where('canRegister', true));
        $this->get('/register')->assertInertia(fn (Assert $page) => $page->where('email', 'ana@example.com'));

        $this->withHeader('Accept-Language', 'en-US')->post('/register', [
            'name' => 'Ana',
            'email' => 'Ana@Example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(url('/invitations/secret-token'));

        $user = User::query()->where('email', 'ana@example.com')->sole();

        $this->assertAuthenticatedAs($user);
        $this->assertFalse($user->is_admin);
        $this->assertSame('en-US', $user->language?->code);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_an_invitation_cannot_be_used_for_another_address(): void
    {
        Invitation::factory()->withToken('secret-token')->create(['email' => 'ana@example.com']);

        $this->get('/invitations/secret-token')->assertOk();
        $this->post('/register', [
            'name' => 'Bob',
            'email' => 'bob@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['email' => 'bob@example.com']);
    }

    public function test_expired_invitations_do_not_open_registration(): void
    {
        User::factory()->create();
        Invitation::factory()->withToken('expired')->expired()->create(['email' => 'ana@example.com']);

        $this->get('/invitations/expired')->assertOk();
        $this->get('/register')->assertRedirect('/login');
    }

    public function test_email_must_be_unique_including_deleted_accounts(): void
    {
        $user = User::factory()->create(['email' => 'ana@example.com']);
        $user->delete();
        Invitation::factory()->withToken('secret-token')->create(['email' => 'ana@example.com']);

        $this->get('/invitations/secret-token');
        $this->post('/register', [
            'name' => 'Ana',
            'email' => 'ana@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('email');
    }

    public function test_authenticated_users_cannot_see_registration(): void
    {
        $this->actingAs(User::factory()->create())->get('/register')->assertRedirect('/dashboard');
    }
}
