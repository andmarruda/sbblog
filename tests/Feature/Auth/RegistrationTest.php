<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_renders(): void
    {
        $this->get('/register')->assertOk();
    }

    public function test_new_users_can_register(): void
    {
        Notification::fake();
        $this->seed(LanguageSeeder::class);

        $this->withHeader('Accept-Language', 'en-US')->post('/register', [
            'name' => 'Ana',
            'email' => 'Ana@Example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/dashboard');

        $user = User::query()->where('email', 'ana@example.com')->sole();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('en-US', $user->language?->code);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_email_must_be_unique_including_deleted_accounts(): void
    {
        $user = User::factory()->create(['email' => 'ana@example.com']);
        $user->delete();

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
