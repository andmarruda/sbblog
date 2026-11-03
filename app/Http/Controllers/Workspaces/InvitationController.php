<?php

namespace App\Http\Controllers\Workspaces;

use App\Actions\Members\InviteMember;
use App\Enums\WorkspaceRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workspaces\StoreInvitationRequest;
use App\Models\Blog;
use App\Models\Invitation;
use App\Models\Role;
use App\Models\User;
use App\Models\Workspace;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class InvitationController extends Controller
{
    public function store(StoreInvitationRequest $request, Workspace $workspace, InviteMember $invite): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();

        $invite->handle(
            $workspace,
            $user,
            $validated['email'],
            WorkspaceRole::from($validated['workspace_role']),
            isset($validated['blog_id']) ? Blog::query()->whereKey($validated['blog_id'])->firstOrFail() : null,
            isset($validated['role_id']) ? Role::query()->whereKey($validated['role_id'])->firstOrFail() : null,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation sent.')]);

        return back();
    }

    public function resend(Workspace $workspace, Invitation $invitation, InviteMember $invite): RedirectResponse
    {
        abort_unless($invitation->workspace_id === $workspace->id && $invitation->accepted_at === null, 404);

        $invite->resend($invitation);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation sent again.')]);

        return back();
    }

    public function destroy(Workspace $workspace, Invitation $invitation, AuditLogger $audit): RedirectResponse
    {
        abort_unless($invitation->workspace_id === $workspace->id && $invitation->accepted_at === null, 404);

        $invitation->delete();
        $audit->record('members.invitation_revoked', null, ['email' => $invitation->email], workspace: $workspace);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation revoked.')]);

        return back();
    }
}
