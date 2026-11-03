<?php

namespace App\Http\Controllers\Workspaces;

use App\Enums\WorkspaceRole;
use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\Role;
use App\Models\User;
use App\Models\Workspace;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    public function index(Request $request, Workspace $workspace): Response
    {
        $blogRoles = DB::table('blog_members')
            ->join('blogs', 'blogs.id', '=', 'blog_members.blog_id')
            ->join('roles', 'roles.id', '=', 'blog_members.role_id')
            ->where('blogs.workspace_id', $workspace->id)
            ->whereNull('blogs.deleted_at')
            ->get(['blog_members.user_id', 'blogs.name as blog', 'roles.key as role'])
            ->groupBy('user_id');

        return Inertia::render('workspace-settings/members', [
            'members' => $workspace->members()
                ->orderBy('name')
                ->get(['users.id', 'users.name', 'users.email'])
                ->map(fn (User $member) => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'workspace_role' => $member->pivot?->getAttribute('workspace_role')?->value,
                    'joined_at' => $member->pivot?->getAttribute('joined_at')?->toIso8601String(),
                    'blogs' => $blogRoles->get($member->id, collect())->values(),
                ]),
            'invitations' => Invitation::query()
                ->whereBelongsTo($workspace)
                ->whereNull('accepted_at')
                ->with(['blog:id,name', 'role:id,key'])
                ->latest()
                ->get()
                ->map(fn (Invitation $invitation) => [
                    'id' => $invitation->id,
                    'email' => $invitation->email,
                    'workspace_role' => $invitation->workspace_role->value,
                    'blog' => $invitation->blog?->name,
                    'role' => $invitation->role?->key,
                    'expires_at' => $invitation->expires_at->toIso8601String(),
                    'expired' => $invitation->expires_at->isPast(),
                ]),
            'blogs' => $workspace->blogs()->orderBy('name')->get(['id', 'name']),
            'roles' => Role::query()->builtIn()->where('key', '!=', 'owner')->orderBy('name')->get(['id', 'key', 'name']),
            'isOwner' => $workspace->owner_user_id === $request->user()?->id,
            'currentUserId' => $request->user()?->id,
        ]);
    }

    public function update(Request $request, Workspace $workspace, User $member, AuditLogger $audit): RedirectResponse
    {
        $validated = $request->validate([
            'workspace_role' => ['required', Rule::in([WorkspaceRole::Admin->value, WorkspaceRole::Member->value])],
        ]);

        $this->ensureNotOwner($workspace, $member);

        $workspace->members()->updateExistingPivot($member->id, ['workspace_role' => $validated['workspace_role']]);
        $audit->record('members.role_changed', $member, $validated, workspace: $workspace);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Member updated.')]);

        return back();
    }

    /**
     * Remove a member (or leave, when it's yourself). The owner can't be removed.
     */
    public function destroy(Workspace $workspace, User $member, AuditLogger $audit): RedirectResponse
    {
        $this->ensureNotOwner($workspace, $member);

        DB::transaction(function () use ($workspace, $member) {
            DB::table('blog_members')
                ->where('user_id', $member->id)
                ->whereIn('blog_id', $workspace->blogs()->withTrashed()->select('id'))
                ->delete();

            $workspace->members()->detach($member->id);
        });

        $audit->record('members.removed', $member, workspace: $workspace);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Member removed.')]);

        return back();
    }

    /**
     * Hand the workspace to another member. The old owner becomes an admin.
     */
    public function transferOwnership(Request $request, Workspace $workspace, User $member, AuditLogger $audit): RedirectResponse
    {
        /** @var User $owner */
        $owner = $request->user();

        abort_unless($workspace->owner_user_id === $owner->id, 403);
        abort_unless($workspace->members()->whereKey($member->id)->exists(), 404);

        $request->validate(['password' => ['required', 'current_password']]);

        if ($member->is($owner)) {
            throw ValidationException::withMessages(['password' => __('You already own this workspace.')]);
        }

        DB::transaction(function () use ($workspace, $owner, $member) {
            $workspace->update(['owner_user_id' => $member->id]);
            $workspace->members()->updateExistingPivot($member->id, ['workspace_role' => WorkspaceRole::Owner]);
            $workspace->members()->updateExistingPivot($owner->id, ['workspace_role' => WorkspaceRole::Admin]);
        });

        $audit->record('workspace.ownership_transferred', $member, ['from' => $owner->id], workspace: $workspace);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Ownership transferred.')]);

        return back();
    }

    private function ensureNotOwner(Workspace $workspace, User $member): void
    {
        abort_unless($workspace->members()->whereKey($member->id)->exists(), 404);

        if ($workspace->owner_user_id === $member->id) {
            throw ValidationException::withMessages(['member' => __('The owner can only change by transferring ownership.')]);
        }
    }
}
