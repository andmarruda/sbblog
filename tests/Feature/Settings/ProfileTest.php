<?php

namespace Tests\Feature\Settings;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Workspace;
use Database\Seeders\LanguageSeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);
    }

    public function test_profile_page_renders(): void
    {
        $this->actingAs(User::factory()->create())->get('/account/profile')->assertOk();
    }

    public function test_profile_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/account/profile', [
            'name' => 'New Name',
            'email' => $user->email,
            'locale' => 'en-US',
        ])->assertRedirect('/account/profile')->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertSame('en-US', $user->language?->code);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(AuditLog::query()->where('action', 'account.updated')->exists());
    }

    public function test_changing_email_requires_verifying_it_again(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/account/profile', [
            'name' => $user->name,
            'email' => 'new@example.com',
            'locale' => 'pt-BR',
        ])->assertSessionHasNoErrors();

        $this->assertNull($user->refresh()->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_email_must_stay_unique(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/account/profile', [
            'name' => $user->name,
            'email' => 'taken@example.com',
            'locale' => 'pt-BR',
        ])->assertSessionHasErrors('email');
    }

    public function test_account_can_be_deleted_with_the_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->delete('/account/profile', ['password' => 'password'])
            ->assertRedirect('/login');

        $this->assertGuest();
        $this->assertSoftDeleted($user);
    }

    public function test_deleting_requires_the_correct_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->delete('/account/profile', ['password' => 'wrong'])
            ->assertSessionHasErrors('password');

        $this->assertNotSoftDeleted($user);
    }

    public function test_workspace_owners_cannot_delete_their_account(): void
    {
        $user = User::factory()->create();
        Workspace::factory()->for($user, 'owner')->create();

        $this->actingAs($user)->delete('/account/profile', ['password' => 'password'])
            ->assertSessionHasErrors('password');

        $this->assertNotSoftDeleted($user);
    }
}
