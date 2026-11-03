<?php

namespace Database\Seeders;

use App\Authorization\Permissions;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Seed the built-in roles. Run after PermissionSeeder.
     */
    public function run(): void
    {
        foreach (Permissions::builtInRoles() as $key => [$name, $permissions]) {
            $role = Role::query()->updateOrCreate(
                ['workspace_id' => null, 'key' => $key],
                ['name' => $name, 'is_system' => true],
            );

            $role->permissions()->sync($permissions);
        }
    }
}
