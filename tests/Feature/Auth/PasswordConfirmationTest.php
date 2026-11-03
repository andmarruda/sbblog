<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirm_screen_renders(): void
    {
        $this->actingAs(User::factory()->create())->get('/confirm-password')->assertOk();
    }

    public function test_password_can_be_confirmed(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/confirm-password', ['password' => 'password'])
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('auth.password_confirmed_at');
    }

    public function test_wrong_password_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/confirm-password', ['password' => 'wrong'])
            ->assertSessionHasErrors('password');
    }
}
