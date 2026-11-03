<?php

namespace App\Authorization;

use App\Enums\PermissionScope;
use App\Enums\WorkspaceRole;
use App\Models\Blog;
use App\Models\Role;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

/**
 * Works out what a user may do in a workspace or blog. Registered as a scoped
 * singleton, so lookups are cached for one request.
 *
 * - Workspace owner: every permission, on every blog.
 * - Workspace admin: the built-in admin role, on every blog.
 * - Workspace member: only the blogs they were added to, with that blog's role.
 */
class PermissionResolver
{
    /** @var array<string, WorkspaceRole|null> */
    private array $workspaceRoles = [];

    /** @var array<string, Role|null> */
    private array $blogRoles = [];

    /** @var array<string, list<string>> */
    private array $rolePermissions = [];

    public function workspaceRole(User $user, Workspace|string $workspace): ?WorkspaceRole
    {
        $workspaceId = $workspace instanceof Workspace ? $workspace->id : $workspace;
        $key = $user->id.'|'.$workspaceId;

        if (! array_key_exists($key, $this->workspaceRoles)) {
            $role = DB::table('workspace_members')
                ->where('workspace_id', $workspaceId)
                ->where('user_id', $user->id)
                ->value('workspace_role');

            $this->workspaceRoles[$key] = is_string($role) ? WorkspaceRole::from($role) : null;
        }

        return $this->workspaceRoles[$key];
    }

    /**
     * The role a user has on a blog. Owners and admins get the matching built-in role.
     */
    public function blogRole(User $user, Blog $blog): ?Role
    {
        $key = $user->id.'|'.$blog->id;

        if (array_key_exists($key, $this->blogRoles)) {
            return $this->blogRoles[$key];
        }

        $role = match ($this->workspaceRole($user, $blog->workspace_id)) {
            WorkspaceRole::Owner => $this->builtIn('owner'),
            WorkspaceRole::Admin => $this->builtIn('admin'),
            WorkspaceRole::Member => Role::query()
                ->whereKey(DB::table('blog_members')
                    ->where('blog_id', $blog->id)
                    ->where('user_id', $user->id)
                    ->value('role_id'))
                ->first(),
            null => null,
        };

        return $this->blogRoles[$key] = $role;
    }

    public function canAccessBlog(User $user, Blog $blog): bool
    {
        return $this->blogRole($user, $blog) !== null;
    }

    /**
     * Workspace-scope permission keys.
     *
     * @return list<string>
     */
    public function forWorkspace(User $user, Workspace $workspace): array
    {
        $role = match ($this->workspaceRole($user, $workspace)) {
            WorkspaceRole::Owner => $this->builtIn('owner'),
            WorkspaceRole::Admin => $this->builtIn('admin'),
            default => null,
        };

        return $role === null ? [] : array_values(array_intersect(
            $this->permissionsOf($role),
            Permissions::inScope(PermissionScope::Workspace),
        ));
    }

    /**
     * Blog-scope permission keys, plus the workspace-scope ones.
     *
     * @return list<string>
     */
    public function forBlog(User $user, Blog $blog): array
    {
        $role = $this->blogRole($user, $blog);

        if ($role === null) {
            return [];
        }

        /** @var Workspace $workspace */
        $workspace = $blog->workspace;

        return array_values(array_unique([
            ...array_intersect($this->permissionsOf($role), Permissions::inScope(PermissionScope::Blog)),
            ...$this->forWorkspace($user, $workspace),
        ]));
    }

    public function has(User $user, string $permission, Workspace|Blog $scope): bool
    {
        $granted = $scope instanceof Blog ? $this->forBlog($user, $scope) : $this->forWorkspace($user, $scope);

        return in_array($permission, $granted, true);
    }

    /**
     * @return list<string>
     */
    private function permissionsOf(Role $role): array
    {
        if ($role->workspace_id === null && $role->key === 'owner') {
            return array_keys(Permissions::ALL);
        }

        return $this->rolePermissions[$role->id] ??= array_values(array_map(
            strval(...),
            DB::table('role_permissions')->where('role_id', $role->id)->pluck('permission_key')->all(),
        ));
    }

    private function builtIn(string $key): ?Role
    {
        return $this->blogRoles['builtin|'.$key] ??= Role::query()->builtIn()->where('key', $key)->first();
    }
}
