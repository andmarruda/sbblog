<?php

namespace App\Http\Controllers\Settings;

use App\Apps\Scopes;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Personal tokens act as the user, limited by the token's scopes and the user's permissions.
 */
class TokenController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('settings/tokens', [
            'tokens' => $user->tokens()->latest()->get()->map(fn (PersonalAccessToken $token) => [
                'id' => $token->id,
                'name' => $token->name,
                'abilities' => $token->abilities,
                'last_used_at' => $token->last_used_at?->toIso8601String(),
                'expires_at' => $token->expires_at?->toIso8601String(),
                'created_at' => $token->created_at?->toIso8601String(),
            ]),
            'scopes' => Scopes::ALL,
            'newToken' => $request->session()->get('new_personal_token'),
        ]);
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => [Rule::in(Scopes::keys())],
            'expires_in_days' => ['nullable', 'integer', Rule::in([30, 90, 365])],
            'password' => ['required', 'current_password'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $token = $user->createToken(
            $validated['name'],
            array_values(array_unique($validated['abilities'])),
            isset($validated['expires_in_days']) ? now()->addDays((int) $validated['expires_in_days']) : null,
        );

        $audit->record('account.token_created', $user, ['name' => $validated['name']]);

        return back()->with('new_personal_token', $token->plainTextToken);
    }

    public function destroy(Request $request, int $token, AuditLogger $audit): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->tokens()->whereKey($token)->firstOrFail()->delete();

        $audit->record('account.token_revoked', $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Token revoked.')]);

        return back();
    }
}
