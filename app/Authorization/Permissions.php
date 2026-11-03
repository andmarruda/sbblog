<?php

namespace App\Authorization;

use App\Enums\PermissionScope;

/**
 * The permission catalog. Seeded into `permissions`; this class is the source of truth.
 */
final class Permissions
{
    /**
     * key => [group, scope, requires_two_factor, description]
     *
     * @var array<string, array{string, PermissionScope, bool, string}>
     */
    public const ALL = [
        'workspace.settings' => ['workspace', PermissionScope::Workspace, false, 'Manage workspace settings'],
        'workspace.billing' => ['workspace', PermissionScope::Workspace, true, 'Manage plan and billing'],
        'members.manage' => ['members', PermissionScope::Workspace, true, 'Invite and remove members, change their roles'],
        'roles.manage' => ['members', PermissionScope::Workspace, true, 'Create and edit custom roles'],
        'blogs.create' => ['blogs', PermissionScope::Workspace, false, 'Create blogs'],
        'blogs.delete' => ['blogs', PermissionScope::Workspace, true, 'Delete and restore blogs'],
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
        'analytics.view' => ['analytics', PermissionScope::Blog, false, "Open the blog's analytics"],
        'audit.view' => ['security', PermissionScope::Blog, false, 'View the audit log'],
    ];

    /**
     * Built-in roles: key => [name, permission keys]. Owner always gets everything.
     *
     * @return array<string, array{string, list<string>}>
     */
    public static function builtInRoles(): array
    {
        $all = array_keys(self::ALL);

        return [
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
    }

    public static function exists(string $key): bool
    {
        return isset(self::ALL[$key]);
    }

    /**
     * @return list<string>
     */
    public static function inScope(PermissionScope $scope): array
    {
        return array_keys(array_filter(self::ALL, fn (array $permission) => $permission[1] === $scope));
    }

    public static function requiresTwoFactor(string $key): bool
    {
        return self::ALL[$key][2] ?? false;
    }
}
