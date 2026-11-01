<?php

namespace Database\Seeders;

use App\Enums\PermissionScope;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Permission catalog: key => [group, scope, requires_two_factor, description].
     *
     * @var array<string, array{string, PermissionScope, bool, string}>
     */
    public const PERMISSIONS = [
        'workspace.settings' => ['workspace', PermissionScope::Workspace, false, 'Manage workspace settings'],
        'workspace.billing' => ['workspace', PermissionScope::Workspace, true, 'Manage plan and billing'],
        'members.manage' => ['members', PermissionScope::Workspace, true, 'Invite and remove members, change their roles'],
        'roles.manage' => ['members', PermissionScope::Workspace, true, 'Create and edit custom roles'],
        'blogs.create' => ['blogs', PermissionScope::Workspace, false, 'Create blogs'],
        'blogs.delete' => ['blogs', PermissionScope::Workspace, true, 'Delete blogs'],
        'apps.manage' => ['apps', PermissionScope::Workspace, false, 'Register and edit apps'],
        'themes.purchase' => ['themes', PermissionScope::Workspace, false, 'Buy premium themes'],

        'blog.settings' => ['blog', PermissionScope::Blog, false, 'Edit blog settings'],
        'articles.view' => ['articles', PermissionScope::Blog, false, 'View articles, including drafts'],
        'articles.create' => ['articles', PermissionScope::Blog, false, 'Create articles'],
        'articles.edit_own' => ['articles', PermissionScope::Blog, false, 'Edit own articles'],
        'articles.edit_any' => ['articles', PermissionScope::Blog, false, 'Edit any article'],
        'articles.publish' => ['articles', PermissionScope::Blog, false, 'Publish, schedule and unpublish articles'],
        'articles.delete' => ['articles', PermissionScope::Blog, false, 'Delete and restore articles'],
        'suggestions.create' => ['articles', PermissionScope::Blog, false, 'Suggest changes to articles'],
        'suggestions.review' => ['articles', PermissionScope::Blog, false, 'Accept or reject suggestions'],
        'categories.manage' => ['taxonomy', PermissionScope::Blog, false, 'Manage categories'],
        'tags.manage' => ['taxonomy', PermissionScope::Blog, false, 'Manage tags'],
        'media.upload' => ['media', PermissionScope::Blog, false, 'Upload media'],
        'media.manage' => ['media', PermissionScope::Blog, false, 'Edit and delete any media'],
        'authors.manage' => ['authors', PermissionScope::Blog, false, 'Manage author profiles'],
        'comments.moderate' => ['comments', PermissionScope::Blog, false, 'Moderate comments'],
        'integrations.manage' => ['integrations', PermissionScope::Blog, false, 'Manage analytics and ads integrations'],
        'scripts.raw' => ['integrations', PermissionScope::Blog, true, 'Add raw custom scripts'],
        'themes.configure' => ['themes', PermissionScope::Blog, false, 'Choose and configure the blog theme'],
        'frontends.manage' => ['publishing', PermissionScope::Blog, true, 'Manage frontends, sync tokens and transports'],
        'apps.install' => ['apps', PermissionScope::Blog, true, 'Install and revoke apps'],
        'analytics.view' => ['analytics', PermissionScope::Blog, false, 'View blog and author analytics'],
        'audit.view' => ['security', PermissionScope::Blog, false, 'View the audit log'],
    ];

    /**
     * Seed the permission catalog.
     */
    public function run(): void
    {
        Permission::query()->upsert(
            collect(self::PERMISSIONS)->map(fn (array $permission, string $key) => [
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
