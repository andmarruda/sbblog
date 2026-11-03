<?php

namespace Tests\Feature\Console;

use App\Models\User;
use App\Models\Workspace;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Support\SessionKey;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_not_found_renders_the_inertia_error_page(): void
    {
        $this->get('/this-page-does-not-exist')
            ->assertNotFound()
            ->assertInertia(fn (Assert $page) => $page
                ->component('errors/error')
                ->where('status', 404));
    }

    public function test_error_page_is_translated_even_for_unmatched_routes(): void
    {
        $this->withHeader('Accept-Language', 'pt-BR')
            ->get('/this-page-does-not-exist')
            ->assertInertia(fn (Assert $page) => $page
                ->where('locale', 'pt-BR')
                ->has('locales'));
    }

    public function test_rate_limited_actions_flash_a_toast(): void
    {
        foreach (range(1, 7) as $attempt) {
            $response = $this->from('/forgot-password')->post('/forgot-password', ['email' => 'a@example.com']);
        }

        $response->assertRedirect('/forgot-password');
        $response->assertSessionHas(SessionKey::FLASH_DATA.'.toast.type', 'error');
    }

    public function test_denials_with_a_reason_show_it(): void
    {
        $this->seed([LanguageSeeder::class, PermissionSeeder::class, RoleSeeder::class]);
        config(['sbblog.two_factor.enforce' => true]);
        $owner = User::factory()->create();
        $workspace = Workspace::factory()->for($owner, 'owner')->create();

        $this->actingAs($owner)->get("/{$workspace->slug}/settings/members")
            ->assertForbidden()
            ->assertInertia(fn (Assert $page) => $page->component('errors/error')->where('reason', __('Enable two-factor authentication to do this.')));
    }

    public function test_api_errors_stay_json(): void
    {
        $this->getJson('/api/this-does-not-exist')
            ->assertNotFound()
            ->assertJsonStructure(['message']);
    }
}
