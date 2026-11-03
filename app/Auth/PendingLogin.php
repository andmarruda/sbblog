<?php

namespace App\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * A login that passed the password step and waits for the second factor. Lives in the session
 * for a few minutes; nothing is authenticated until complete().
 */
class PendingLogin
{
    private const MINUTES = 10;

    public function start(Request $request, User $user, bool $remember): void
    {
        $request->session()->put('login.pending', [
            'id' => $user->id,
            'remember' => $remember,
            'expires_at' => now()->addMinutes(self::MINUTES)->getTimestamp(),
        ]);
    }

    public function user(Request $request): ?User
    {
        $pending = $request->session()->get('login.pending');

        if (! is_array($pending) || ($pending['expires_at'] ?? 0) < now()->getTimestamp()) {
            return null;
        }

        return User::query()->whereKey((string) ($pending['id'] ?? ''))->first();
    }

    public function complete(Request $request, User $user, ?bool $remember = null): void
    {
        $remember ??= (bool) ($request->session()->get('login.pending')['remember'] ?? false);
        $request->session()->forget('login.pending');

        Auth::guard('web')->login($user, $remember);
        $request->session()->regenerate();
    }
}
