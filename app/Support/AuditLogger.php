<?php

namespace App\Support;

use App\Models\AppInstallation;
use App\Models\AuditLog;
use App\Models\Blog;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogger
{
    public function __construct(private readonly Request $request) {}

    /**
     * Record an action, e.g. "auth.login" or "members.invited".
     *
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        string $action,
        ?Model $subject = null,
        array $metadata = [],
        ?Workspace $workspace = null,
        ?Blog $blog = null,
        ?User $actor = null,
        ?AppInstallation $app = null,
    ): AuditLog {
        $actor ??= $app === null ? $this->request->user() : null;

        return AuditLog::query()->create([
            'workspace_id' => $workspace->id ?? $blog?->workspace_id,
            'blog_id' => $blog?->id,
            'actor_user_id' => $actor?->id,
            'actor_app_installation_id' => $app?->id,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'metadata' => $metadata,
            'ip_hash' => IpHash::make($this->request->ip()),
        ]);
    }
}
