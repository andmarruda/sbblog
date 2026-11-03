<?php

namespace App\Actions\Members;

use App\Enums\WorkspaceRole;
use App\Models\Blog;
use App\Models\Invitation;
use App\Models\Role;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\WorkspaceInvitation;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class InviteMember
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Invite an email. A pending invitation for the same email is replaced.
     */
    public function handle(
        Workspace $workspace,
        User $inviter,
        string $email,
        WorkspaceRole $workspaceRole,
        ?Blog $blog = null,
        ?Role $role = null,
    ): Invitation {
        $email = Str::lower(trim($email));
        $token = Str::random(48);

        $invitation = DB::transaction(function () use ($workspace, $inviter, $email, $workspaceRole, $blog, $role, $token) {
            $workspace->invitations()->where('email', $email)->whereNull('accepted_at')->delete();

            return Invitation::query()->create([
                'workspace_id' => $workspace->id,
                'email' => $email,
                'workspace_role' => $workspaceRole,
                'blog_id' => $blog?->id,
                'role_id' => $blog === null ? null : $role?->id,
                'token_hash' => Invitation::hashToken($token),
                'invited_by' => $inviter->id,
                'expires_at' => now()->addDays((int) config('sbblog.invitations.expire_days')),
            ]);
        });

        $this->send($invitation, $token);

        $this->audit->record('members.invited', $invitation, ['email' => $email, 'workspace_role' => $workspaceRole->value], workspace: $workspace);

        return $invitation;
    }

    /**
     * Issue a fresh token and expiry, and email it again.
     */
    public function resend(Invitation $invitation): void
    {
        $token = Str::random(48);

        $invitation->update([
            'token_hash' => Invitation::hashToken($token),
            'expires_at' => now()->addDays((int) config('sbblog.invitations.expire_days')),
        ]);

        $this->send($invitation, $token);
    }

    private function send(Invitation $invitation, string $token): void
    {
        Notification::route('mail', $invitation->email)
            ->notify((new WorkspaceInvitation($invitation, $token))->locale(app()->getLocale()));
    }
}
