<?php

namespace App\Support;

use App\Enums\WorkspaceRole;
use App\Models\Blog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TenantDirectory
{
    /**
     * Workspaces the user belongs to, each with the blogs they can open.
     *
     * @return array<int, array{id: string, name: string, slug: string, role: string, blogs: array<int, array{id: string, name: string, slug: string}>}>
     */
    public static function for(User $user): array
    {
        $memberships = DB::table('workspace_members')
            ->join('workspaces', 'workspaces.id', '=', 'workspace_members.workspace_id')
            ->whereNull('workspaces.deleted_at')
            ->where('workspace_members.user_id', $user->id)
            ->orderBy('workspaces.name')
            ->get(['workspaces.id', 'workspaces.name', 'workspaces.slug', 'workspace_members.workspace_role']);

        if ($memberships->isEmpty()) {
            return [];
        }

        $memberBlogIds = DB::table('blog_members')->where('user_id', $user->id)->pluck('blog_id')->all();

        $blogs = Blog::query()
            ->whereIn('workspace_id', $memberships->pluck('id'))
            ->orderBy('name')
            ->get(['id', 'workspace_id', 'name', 'slug'])
            ->groupBy('workspace_id');

        return $memberships->map(function (object $membership) use ($blogs, $memberBlogIds) {
            $seesAll = $membership->workspace_role !== WorkspaceRole::Member->value;

            return [
                'id' => (string) $membership->id,
                'name' => (string) $membership->name,
                'slug' => (string) $membership->slug,
                'role' => (string) $membership->workspace_role,
                'blogs' => collect($blogs->get($membership->id, []))
                    ->filter(fn (Blog $blog) => $seesAll || in_array($blog->id, $memberBlogIds, true))
                    ->map(fn (Blog $blog) => ['id' => $blog->id, 'name' => $blog->name, 'slug' => $blog->slug])
                    ->values()
                    ->all(),
            ];
        })->values()->all();
    }
}
