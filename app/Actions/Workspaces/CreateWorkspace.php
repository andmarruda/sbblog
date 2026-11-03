<?php

namespace App\Actions\Workspaces;

use App\Enums\TwoFactorPolicy;
use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Support\AuditLogger;
use App\Support\Slug;
use Illuminate\Support\Facades\DB;

class CreateWorkspace
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(User $owner, string $name, ?string $slug = null): Workspace
    {
        return DB::transaction(function () use ($owner, $name, $slug) {
            $workspace = Workspace::query()->create([
                'name' => $name,
                'slug' => $slug ?? self::suggestSlug($name),
                'owner_user_id' => $owner->id,
                'two_factor_policy' => TwoFactorPolicy::Off,
                'plan' => 'community',
            ]);

            $workspace->members()->attach($owner->id, ['workspace_role' => WorkspaceRole::Owner]);

            $this->audit->record('workspace.created', $workspace, workspace: $workspace, actor: $owner);

            return $workspace;
        });
    }

    public static function suggestSlug(string $name): string
    {
        return Slug::unique(
            $name,
            fn (string $slug) => Workspace::withTrashed()->where('slug', $slug)->exists(),
            config('sbblog.reserved_slugs'),
        );
    }
}
