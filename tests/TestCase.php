<?php

namespace Tests;

use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Event;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Feature tests don't need built assets.
        $this->withoutVite();

        // Each request in production gets fresh scoped services (permission caches…); mirror
        // that when one test makes several requests.
        Event::listen(RequestHandled::class, fn () => $this->app->forgetScopedInstances());
    }
}
