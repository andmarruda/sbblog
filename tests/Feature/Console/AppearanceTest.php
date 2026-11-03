<?php

namespace Tests\Feature\Console;

use Tests\TestCase;

class AppearanceTest extends TestCase
{
    public function test_dark_cookie_renders_the_dark_class(): void
    {
        $this->withUnencryptedCookie('appearance', 'dark')
            ->get('/login')
            ->assertSee('class="dark"', escape: false);
    }

    public function test_invalid_cookie_falls_back_to_system(): void
    {
        $this->withUnencryptedCookie('appearance', '"><script>')
            ->get('/login')
            ->assertDontSee('"><script>', escape: false)
            ->assertSee("const appearance = 'system'", escape: false);
    }
}
