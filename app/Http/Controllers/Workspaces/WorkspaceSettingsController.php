<?php

namespace App\Http\Controllers\Workspaces;

use App\Enums\TwoFactorPolicy;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workspaces\UpdateWorkspaceRequest;
use App\Models\User;
use App\Models\Workspace;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceSettingsController extends Controller
{
    public function edit(Request $request, Workspace $workspace): Response
    {
        return Inertia::render('workspace-settings/general', [
            'workspace' => $workspace->only(['id', 'name', 'slug', 'plan']) + [
                'two_factor_policy' => $workspace->two_factor_policy->value,
            ],
            'twoFactorPolicies' => array_map(fn (TwoFactorPolicy $policy) => $policy->value, TwoFactorPolicy::cases()),
            'isOwner' => $workspace->owner_user_id === $request->user()?->id,
        ]);
    }

    public function update(UpdateWorkspaceRequest $request, Workspace $workspace, AuditLogger $audit): RedirectResponse
    {
        $workspace->fill($request->validated());
        $changed = array_keys($workspace->getDirty());
        $workspace->save();

        if ($changed !== []) {
            $audit->record('workspace.updated', $workspace, ['fields' => $changed], workspace: $workspace);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Workspace updated.')]);

        // The slug is part of the URL.
        return to_route('workspaces.settings.general', $workspace->slug);
    }

    /**
     * Only the owner can delete a workspace, after typing its slug and their password.
     */
    public function destroy(Request $request, Workspace $workspace, AuditLogger $audit): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($workspace->owner_user_id === $user->id, 403);
        // Through the gate too, so the 2FA rule for destructive permissions applies.
        Gate::authorize('blogs.delete', $workspace);

        $request->validate([
            'confirmation' => ['required', Rule::in([$workspace->slug])],
            'password' => ['required', 'current_password'],
        ]);

        $audit->record('workspace.deleted', $workspace, workspace: $workspace);
        $workspace->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Workspace deleted.')]);

        return to_route('dashboard');
    }
}
