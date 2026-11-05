<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_admins_see_every_account(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Ana']);
        User::factory()->create(['name' => 'Bob']);

        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get('/admin/users')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/users')
                ->has('users.data', 2)
                ->where('counts', ['users' => 2, 'admins' => 1])
                ->where('auth.user.is_admin', true));
    }

    public function test_admin_list_can_be_searched_and_filtered(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Ana']);
        User::factory()->create(['name' => 'Bob', 'email' => 'bob@example.com']);

        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()]);

        $this->get('/admin/users?search=BOB@')
            ->assertInertia(fn (Assert $page) => $page->has('users.data', 1)->where('users.data.0.name', 'Bob'));
        $this->get('/admin/users?role=admin')
            ->assertInertia(fn (Assert $page) => $page->has('users.data', 1)->where('users.data.0.name', 'Ana'));
    }

    public function test_admin_area_asks_for_the_password_first(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/users')
            ->assertRedirect('/confirm-password');
    }

    public function test_other_users_cannot_open_the_admin_area(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get('/admin/users')
            ->assertForbidden();

        $this->actingAs($user)
            ->put("/admin/users/{$user->id}/admin", ['is_admin' => true])
            ->assertForbidden();

        $this->assertFalse($user->refresh()->is_admin);
    }

    public function test_admins_can_grant_and_revoke_admin_access(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()]);

        $this->put("/admin/users/{$user->id}/admin", ['is_admin' => true])->assertRedirect();
        $this->assertTrue($user->refresh()->is_admin);

        $this->put("/admin/users/{$user->id}/admin", ['is_admin' => false])->assertRedirect();
        $this->assertFalse($user->refresh()->is_admin);

        $this->assertSame(
            ['admin.granted', 'admin.revoked'],
            AuditLog::query()->where('subject_id', $user->id)->orderBy('created_at')->orderBy('id')->pluck('action')->all(),
        );
    }

    public function test_admins_cannot_change_their_own_access(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->put("/admin/users/{$admin->id}/admin", ['is_admin' => false])
            ->assertForbidden();

        $this->assertTrue($admin->refresh()->is_admin);
    }

    public function test_command_grants_and_revokes_admin_access(): void
    {
        $ana = User::factory()->create(['email' => 'ana@example.com']);

        $this->artisan('sbblog:admin', ['email' => 'ANA@example.com'])->assertSuccessful();
        $this->assertTrue($ana->refresh()->is_admin);

        // Never leaves the instance without an administrator.
        $this->artisan('sbblog:admin', ['email' => 'ana@example.com', '--revoke' => true])->assertFailed();
        $this->assertTrue($ana->refresh()->is_admin);

        User::factory()->admin()->create();
        $this->artisan('sbblog:admin', ['email' => 'ana@example.com', '--revoke' => true])->assertSuccessful();
        $this->assertFalse($ana->refresh()->is_admin);

        $this->artisan('sbblog:admin', ['email' => 'nobody@example.com'])->assertFailed();
    }
}
