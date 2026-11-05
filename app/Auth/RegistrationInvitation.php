<?php

namespace App\Auth;

use App\Models\Invitation;
use Illuminate\Http\Request;

/**
 * Sign-up is invite-only: an account can be created only for the address of a pending
 * invitation the visitor opened in this session.
 */
class RegistrationInvitation
{
    public const SESSION_KEY = 'invitation_id';

    public function remember(Request $request, Invitation $invitation): void
    {
        $request->session()->put(self::SESSION_KEY, $invitation->id);
        $request->session()->put('invitation_email', $invitation->email);
    }

    public function forget(Request $request): void
    {
        $request->session()->forget([self::SESSION_KEY, 'invitation_email']);
    }

    public function current(Request $request): ?Invitation
    {
        $id = $request->session()->get(self::SESSION_KEY);

        if (! is_string($id)) {
            return null;
        }

        $invitation = Invitation::query()->find($id);

        return $invitation !== null && $invitation->isPending() && $invitation->workspace !== null
            ? $invitation
            : null;
    }
}
