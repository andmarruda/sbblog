<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuditLogger;
use App\Support\UserAgent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SessionController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $sessions = config('session.driver') !== 'database' ? collect() : DB::table('sessions')
            ->where('user_id', $user->id)
            ->orderByDesc('last_activity')
            ->get()
            ->map(fn (object $session) => [
                'id' => $session->id,
                'ip_address' => $session->ip_address,
                'agent' => UserAgent::parse($session->user_agent),
                'is_current' => $session->id === $request->session()->getId(),
                'last_active_at' => Carbon::createFromTimestamp($session->last_activity)->toIso8601String(),
            ]);

        return Inertia::render('settings/sessions', [
            'sessions' => $sessions,
        ]);
    }

    /**
     * Log out every other browser. Requires the password, since it also rotates the hash
     * that remember-me cookies are checked against.
     */
    public function destroyOthers(Request $request, AuditLogger $audit): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        /** @var User $user */
        $user = $request->user();

        Auth::logoutOtherDevices($validated['password']);

        if (config('session.driver') === 'database') {
            DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('id', '!=', $request->session()->getId())
                ->delete();
        }

        $audit->record('account.sessions_revoked', $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Other sessions were logged out.')]);

        return back();
    }
}
