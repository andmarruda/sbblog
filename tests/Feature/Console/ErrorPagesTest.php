<?php

namespace Tests\Feature\Console;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
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

    public function test_api_errors_stay_json(): void
    {
        $this->getJson('/api/this-does-not-exist')
            ->assertNotFound()
            ->assertJsonStructure(['message']);
    }
}
