<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AboutPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_read_the_about_page(): void
    {
        $this->get('/about')->assertOk()->assertInertia(fn (Assert $page) => $page->component('about'));
    }

    public function test_signed_in_users_can_read_the_about_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/about')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('about'));
    }
}
