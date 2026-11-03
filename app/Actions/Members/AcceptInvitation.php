<?php

namespace App\Actions\Members;

use App\Enums\WorkspaceRole;
use App\Models\Invitation;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcceptInvitation
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(Invitation $invitation, User $user): void
    {
        if (! $invitation->isPending()) {
            throw ValidationException::withMessages(['invitation' => __('This invitation is no longer valid.')]);
        }

        if (mb_strtolower($user->email) !== $invitation->email) {
            throw ValidationException::withMessages([
                'invitation' => __('This invitation was sent to :email. Log in with that account to accept it.', ['email' => $invitation->email]),
            ]);
        }

        DB::transaction(function () use ($invitation, $user) {
            $workspace = $invitation->workspace()->firstOrFail();
            $current = $workspace->members()->whereKey($user->id)->first()?->pivot;

            if ($current === null) {
                $workspace->members()->attach($user->id, ['workspace_role' => $invitation->workspace_role]);
            } elseif ($current->getAttribute('workspace_role') === WorkspaceRole::Member && $invitation->workspace_role === WorkspaceRole::Admin) {
                // Never downgrade an existing member; only upgrade.
                $workspace->members()->updateExistingPivot($user->id, ['workspace_role' => WorkspaceRole::Admin]);
            }

            if ($invitation->blog_id !== null && $invitation->role_id !== null) {
                DB::table('blog_members')->upsert(
                    [['blog_id' => $invitation->blog_id, 'user_id' => $user->id, 'role_id' => $invitation->role_id, 'invited_by' => $invitation->invited_by, 'created_at' => now()]],
                    uniqueBy: ['blog_id', 'user_id'],
                    update: ['role_id'],
                );
            }

            $invitation->update(['accepted_at' => now()]);

            // Following the emailed link proves the address.
            if (! $user->hasVerifiedEmail()) {
                $user->markEmailAsVerified();
            }

            $this->audit->record('members.joined', $user, ['invitation_id' => $invitation->id], workspace: $workspace, actor: $user);
        });
    }
}
