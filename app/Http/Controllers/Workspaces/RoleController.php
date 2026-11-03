<?php

namespace App\Http\Controllers\Workspaces;

use App\Authorization\Permissions;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Workspace;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    /**
     * Built-in roles and what they grant. Custom roles arrive with billing (2.6).
     */
    public function index(Workspace $workspace): Response
    {
        $roles = Role::query()->availableTo($workspace)->with('permissions:key')->orderBy('name')->get();

        return Inertia::render('workspace-settings/roles', [
            'roles' => $roles->map(fn (Role $role) => [
                'id' => $role->id,
                'key' => $role->key,
                'name' => $role->name,
                'is_system' => $role->is_system,
                'permissions' => $role->key === 'owner' && $role->is_system
                    ? array_keys(Permissions::ALL)
                    : $role->permissions->pluck('key')->all(),
            ]),
            'permissions' => collect(Permissions::ALL)->map(fn (array $permission, string $key) => [
                'key' => $key,
                'group' => $permission[0],
                'scope' => $permission[1]->value,
                'requires_two_factor' => $permission[2],
                'description' => $permission[3],
            ])->values(),
        ]);
    }
}
