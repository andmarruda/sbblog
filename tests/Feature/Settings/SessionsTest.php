<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SessionsTest extends TestCase
{
    use RefreshDatabase;

    private function seedSessions(User $user): void
    {
        DB::table('sessions')->insert([
            [
                'id' => 'other-session',
                'user_id' => $user->id,
                'ip_address' => '10.0.0.1',
                'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148 Safari/604.1',
                'payload' => '',
                'last_activity' => now()->subHour()->timestamp,
            ],
            [
                'id' => 'someone-else',
                'user_id' => User::factory()->create()->id,
                'ip_address' => '10.0.0.2',
                'user_agent' => null,
                'payload' => '',
                'last_activity' => now()->timestamp,
            ],
        ]);
    }

    public function test_lists_only_the_users_sessions(): void
    {
        config(['session.driver' => 'database']);
        $user = User::factory()->create();
        $this->seedSessions($user);

        $this->actingAs($user)->get('/account/sessions')
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/sessions')
                ->has('sessions', 1)
                ->where('sessions.0.agent.platform', 'iOS')
                ->where('sessions.0.agent.mobile', true));
    }

    public function test_other_sessions_can_be_logged_out(): void
    {
        config(['session.driver' => 'database']);
        $user = User::factory()->create();
        $this->seedSessions($user);

        $this->actingAs($user)->delete('/account/sessions', ['password' => 'password'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('sessions', ['id' => 'other-session']);
        $this->assertDatabaseHas('sessions', ['id' => 'someone-else']);
    }

    public function test_logging_out_others_requires_the_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->delete('/account/sessions', ['password' => 'wrong'])
            ->assertSessionHasErrors('password');
    }
}
