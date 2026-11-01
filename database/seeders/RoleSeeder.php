<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Seed the built-in roles. Run after PermissionSeeder.
     */
    public function run(): void
    {
        $all = array_keys(PermissionSeeder::PERMISSIONS);

        $roles = [
            'owner' => ['Owner', $all],
            'admin' => ['Admin', array_values(array_diff($all, ['workspace.billing', 'blogs.delete']))],
            'editor' => ['Editor', [
                'articles.view', 'articles.create', 'articles.edit_own', 'articles.edit_any',
                'articles.publish', 'articles.delete', 'suggestions.create', 'suggestions.review',
                'categories.manage', 'tags.manage', 'media.upload', 'media.manage',
                'authors.manage', 'comments.moderate', 'analytics.view',
            ]],
            'author' => ['Author', [
                'articles.view', 'articles.create', 'articles.edit_own', 'suggestions.create',
                'media.upload', 'analytics.view',
            ]],
            'reviewer' => ['Reviewer', [
                'articles.view', 'suggestions.create', 'suggestions.review', 'comments.moderate',
            ]],
            'designer' => ['Designer', [
                'articles.view', 'themes.configure', 'media.upload', 'media.manage',
            ]],
            'viewer' => ['Viewer', ['articles.view', 'analytics.view']],
        ];

        foreach ($roles as $key => [$name, $permissions]) {
            $role = Role::query()->updateOrCreate(
                ['workspace_id' => null, 'key' => $key],
                ['name' => $name, 'is_system' => true],
            );

            $role->permissions()->sync($permissions);
        }
    }
}
