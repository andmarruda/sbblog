<?php

namespace App\Http\Controllers\Auth;

use App\Auth\Passkeys;
use App\Auth\PendingLogin;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Passwordless sign-in with a passkey the browser offers. The authenticator verifies the person
 * (PIN or biometrics), so no second step follows.
 */
class PasskeyLoginController extends Controller
{
    public function options(Request $request, Passkeys $passkeys): JsonResponse
    {
        return response()->json($passkeys->assertionOptions($request));
    }

    public function store(Request $request, Passkeys $passkeys, PendingLogin $login): JsonResponse
    {
        $validated = $request->validate(['credential' => ['required', 'array']]);

        $credential = $passkeys->verify((array) $validated['credential'], $request);
        $login->complete($request, $credential->user()->firstOrFail(), false);

        return response()->json(['redirect' => redirect()->intended(route('dashboard', absolute: false))->getTargetUrl()]);
    }
}
