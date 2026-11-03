<?php

namespace Database\Seeders;

use App\Authorization\Permissions;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Seed the permission catalog.
     */
    public function run(): void
    {
        Permission::query()->upsert(
            collect(Permissions::ALL)->map(fn (array $permission, string $key) => [
                'key' => $key,
                'group' => $permission[0],
                'scope' => $permission[1]->value,
                'requires_two_factor' => $permission[2],
                'description' => $permission[3],
            ])->values()->all(),
            uniqueBy: ['key'],
            update: ['group', 'scope', 'requires_two_factor', 'description'],
        );
    }
}
