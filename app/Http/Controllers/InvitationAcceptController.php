<?php

namespace App\Http\Controllers;

use App\Actions\Members\AcceptInvitation;
use App\Auth\RegistrationInvitation;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvitationAcceptController extends Controller
{
    /**
     * Public landing page for the emailed link. Guests are sent back here after logging in or
     * signing up.
     */
    public function show(Request $request, string $token, RegistrationInvitation $invitations): Response
    {
        $invitation = Invitation::findByToken($token);
        $user = $request->user();

        if ($user === null) {
            $request->session()->put('url.intended', $request->fullUrl());

            if ($invitation !== null) {
                $invitations->remember($request, $invitation);
            }
        }

        return Inertia::render('invitations/show', [
            'token' => $token,
            'invitation' => $invitation === null ? null : [
                'email' => $invitation->email,
                'workspace' => $invitation->workspace?->name,
                'inviter' => $invitation->inviter?->name,
                'blog' => $invitation->blog?->name,
                'valid' => $invitation->isPending() && $invitation->workspace !== null,
            ],
            'emailMatches' => $user instanceof User && $invitation !== null
                && mb_strtolower($user->email) === $invitation->email,
        ]);
    }

    public function accept(Request $request, string $token, AcceptInvitation $accept, RegistrationInvitation $invitations): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $invitation = Invitation::findByToken($token);

        $workspace = $invitation?->workspace;

        abort_if($invitation === null || $workspace === null, 404);

        $accept->handle($invitation, $user);
        $invitations->forget($request);

        $blog = $invitation->blog;

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Welcome to :workspace!', ['workspace' => $workspace->name])]);

        return $blog !== null
            ? to_route('blogs.dashboard', [$workspace->slug, $blog->slug])
            : to_route('dashboard');
    }
}
