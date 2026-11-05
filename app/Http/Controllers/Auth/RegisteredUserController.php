<?php

namespace App\Http\Controllers\Auth;

use App\Auth\InstanceSetup;
use App\Auth\RegistrationInvitation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Language;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Sign-up is invite-only: it opens from an invitation link, for that invitation's address.
     */
    public function create(Request $request, InstanceSetup $setup, RegistrationInvitation $invitations): Response|RedirectResponse
    {
        if ($setup->isPending()) {
            return to_route('setup');
        }

        $invitation = $invitations->current($request);

        if ($invitation === null) {
            Inertia::flash('toast', ['type' => 'info', 'message' => __('Sign-up is by invitation only. Ask a workspace admin to invite you.')]);

            return to_route('login');
        }

        return Inertia::render('auth/register', [
            'email' => $invitation->email,
        ]);
    }

    public function store(RegisterRequest $request, RegistrationInvitation $invitations): RedirectResponse
    {
        $invitation = $invitations->current($request);

        abort_if($invitation === null, 403, __('Sign-up is by invitation only. Ask a workspace admin to invite you.'));

        if ($request->validated('email') !== $invitation->email) {
            throw ValidationException::withMessages([
                'email' => __('Use the address the invitation was sent to: :email.', ['email' => $invitation->email]),
            ]);
        }

        $user = User::query()->create([
            ...$request->safe()->only(['name', 'email', 'password']),
            // Keep the language the visitor was browsing in.
            'language_id' => Language::query()->where('code', App::getLocale())->value('id'),
        ]);

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
