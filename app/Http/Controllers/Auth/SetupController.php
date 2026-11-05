<?php

namespace App\Http\Controllers\Auth;

use App\Auth\InstanceSetup;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SetupRequest;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Creates the first administrator of a fresh install. Gone (404) once any account exists.
 */
class SetupController extends Controller
{
    public function create(InstanceSetup $setup): Response
    {
        abort_unless($setup->isPending(), 404);

        return Inertia::render('auth/setup');
    }

    public function store(SetupRequest $request, InstanceSetup $setup, AuditLogger $audit): RedirectResponse
    {
        /** @var array{name: string, email: string, password: string} $attributes */
        $attributes = $request->safe()->only(['name', 'email', 'password']);
        $user = $setup->createAdministrator($attributes);

        abort_if($user === null, 404);

        Auth::login($user);
        $request->session()->regenerate();

        $audit->record('instance.setup', $user, actor: $user);

        return to_route('onboarding');
    }
}
