<?php

namespace App\Http\Controllers\Auth;

use App\Auth\PendingLogin;
use App\Auth\TrustedDevices;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('auth/login', [
            'status' => $request->session()->get('status'),
        ]);
    }

    public function store(LoginRequest $request, PendingLogin $login, TrustedDevices $devices): RedirectResponse
    {
        $user = $request->validateCredentials();
        $remember = $request->boolean('remember');

        if ($user->hasTwoFactorEnabled() && ! $devices->isTrusted($user, $request)) {
            $login->start($request, $user, $remember);

            return to_route('two-factor.challenge');
        }

        $login->complete($request, $user, $remember);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
