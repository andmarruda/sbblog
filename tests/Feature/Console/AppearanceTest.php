<?php

namespace Tests\Feature\Console;

use Tests\TestCase;

class AppearanceTest extends TestCase
{
    public function test_dark_cookie_renders_the_dark_class(): void
    {
        $this->withUnencryptedCookie('appearance', 'dark')
            ->get('/forgot-password')
            ->assertSee('class="dark"', escape: false);
    }

    public function test_security_headers_are_sent(): void
    {
        $this->get('/forgot-password')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_invalid_cookie_falls_back_to_system(): void
    {
        $this->withUnencryptedCookie('appearance', '"><script>')
            ->get('/forgot-password')
            ->assertDontSee('"><script>', escape: false)
            ->assertSee("const appearance = 'system'", escape: false);
    }
}
