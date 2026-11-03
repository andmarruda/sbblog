<?php

namespace Tests\Feature\Console;

use App\Models\Language;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_defaults_to_brazilian_portuguese(): void
    {
        // Symfony's test requests send "Accept-Language: en-us" unless told otherwise.
        $this->withHeader('Accept-Language', '')
            ->get('/login')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('locale', 'pt-BR')
                ->where('locales', ['pt-BR', 'en-US']));
    }

    public function test_uses_the_browser_language_when_supported(): void
    {
        $this->withHeader('Accept-Language', 'en-US,en;q=0.9')
            ->get('/login')
            ->assertInertia(fn (Assert $page) => $page->where('locale', 'en-US'));
    }

    public function test_ignores_unsupported_browser_languages(): void
    {
        $this->withHeader('Accept-Language', 'de-DE')
            ->get('/login')
            ->assertInertia(fn (Assert $page) => $page->where('locale', 'pt-BR'));
    }

    public function test_guest_can_switch_language_with_a_cookie(): void
    {
        $this->withHeader('Accept-Language', 'pt-BR')
            ->from('/login')
            ->put('/locale', ['locale' => 'en-US'])
            ->assertRedirect('/login')
            ->assertCookie('locale', 'en-US', encrypted: false);

        $this->withUnencryptedCookie('locale', 'en-US')
            ->withHeader('Accept-Language', 'pt-BR')
            ->get('/login')
            ->assertInertia(fn (Assert $page) => $page->where('locale', 'en-US'));
    }

    public function test_switching_language_is_saved_on_the_user(): void
    {
        $this->seed(LanguageSeeder::class);
        $user = User::factory()->create();

        $this->actingAs($user)->put('/locale', ['locale' => 'en-US']);

        $this->assertSame('en-US', $user->refresh()->language?->code);
    }

    public function test_user_preference_wins_over_the_browser(): void
    {
        $this->seed(LanguageSeeder::class);
        $user = User::factory()->create([
            'language_id' => Language::query()->where('code', 'en-US')->value('id'),
        ]);

        $this->actingAs($user)
            ->withHeader('Accept-Language', 'pt-BR')
            ->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page->where('locale', 'en-US'));
    }

    public function test_rejects_unsupported_languages(): void
    {
        $this->put('/locale', ['locale' => 'xx-XX'])->assertSessionHasErrors('locale');
    }

    public function test_validation_messages_are_translated(): void
    {
        $this->withHeader('Accept-Language', 'pt-BR')
            ->put('/locale', [])
            ->assertSessionHasErrors([
                'locale' => 'O campo locale é obrigatório.',
            ]);
    }
}
