<?php

namespace App\Http\Controllers\Blogs;

use App\Enums\WorkspaceRole;
use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Role;
use App\Models\User;
use App\Models\Workspace;
use App\Support\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class BlogMemberController extends Controller
{
    public function index(Workspace $workspace, Blog $blog): Response
    {
        $members = $workspace->members()->orderBy('name')->get(['users.id', 'users.name', 'users.email']);
        $blogRoles = DB::table('blog_members')->where('blog_id', $blog->id)->pluck('role_id', 'user_id');

        return Inertia::render('blog-settings/members', [
            // Owners and admins reach every blog; only members are assigned per blog.
            'members' => $members->map(fn (User $member) => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'workspace_role' => $member->pivot?->getAttribute('workspace_role')?->value,
                'role_id' => $blogRoles[$member->id] ?? null,
            ]),
            'roles' => $this->assignableRoles($workspace)->get(['id', 'key', 'name']),
        ]);
    }

    /**
     * Give a workspace member a role on this blog, or change it.
     */
    public function update(Request $request, Workspace $workspace, Blog $blog, User $member, AuditLogger $audit): RedirectResponse
    {
        $validated = $request->validate([
            'role_id' => ['required', 'uuid', Rule::in($this->assignableRoles($workspace)->pluck('id'))],
        ]);

        $workspaceRole = $workspace->members()->whereKey($member->id)->first()?->pivot?->getAttribute('workspace_role');

        abort_if($workspaceRole === null, 404);

        if ($workspaceRole !== WorkspaceRole::Member) {
            throw ValidationException::withMessages(['role_id' => __('Owners and admins already have access to every blog.')]);
        }

        DB::table('blog_members')->upsert(
            [['blog_id' => $blog->id, 'user_id' => $member->id, 'role_id' => $validated['role_id'], 'invited_by' => $request->user()?->id, 'created_at' => now()]],
            uniqueBy: ['blog_id', 'user_id'],
            update: ['role_id'],
        );

        $audit->record('blog.member_role_changed', $member, $validated, blog: $blog);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Member updated.')]);

        return back();
    }

    public function destroy(Workspace $workspace, Blog $blog, User $member, AuditLogger $audit): RedirectResponse
    {
        DB::table('blog_members')->where('blog_id', $blog->id)->where('user_id', $member->id)->delete();

        $audit->record('blog.member_removed', $member, blog: $blog);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Access removed.')]);

        return back();
    }

    /**
     * @return Builder<Role>
     */
    private function assignableRoles(Workspace $workspace): Builder
    {
        return Role::query()->availableTo($workspace)->where('key', '!=', 'owner')->orderBy('name');
    }
}
