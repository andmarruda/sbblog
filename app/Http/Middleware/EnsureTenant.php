<?php

namespace App\Http\Middleware;

use App\Authorization\PermissionResolver;
use App\Authorization\TwoFactorRequirement;
use App\Models\Blog;
use App\Models\User;
use App\Models\Workspace;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards /{workspace}/… and /{workspace}/{blog}/… routes. Non-members get a 404, never a 403,
 * so the existence of a workspace or blog doesn't leak.
 */
class EnsureTenant
{
    public function __construct(
        private readonly PermissionResolver $permissions,
        private readonly TwoFactorRequirement $twoFactor,
    ) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User $user */
        $user = $request->user();
        $workspace = $request->route('workspace');
        $blog = $request->route('blog');

        abort_unless($workspace instanceof Workspace, 404);
        abort_if($this->permissions->workspaceRole($user, $workspace) === null, 404);

        if ($blog instanceof Blog) {
            abort_unless($this->permissions->canAccessBlog($user, $blog), 404);
            $blog->setRelation('workspace', $workspace);
        }

        if ($this->twoFactor->blocks($user, $blog instanceof Blog ? $blog : $workspace)) {
            Inertia::flash('toast', ['type' => 'warning', 'message' => __('This workspace requires two-factor authentication. Turn it on to continue.')]);

            return to_route('security.edit');
        }

        if ($blog instanceof Blog && $request->isMethod('GET')) {
            $user->rememberPreference('last_blog_id', $blog->id);
        }

        Inertia::share([
            'currentWorkspace' => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'slug' => $workspace->slug,
                'role' => $this->permissions->workspaceRole($user, $workspace)?->value,
            ],
            'currentBlog' => $blog instanceof Blog ? [
                'id' => $blog->id,
                'name' => $blog->name,
                'slug' => $blog->slug,
            ] : null,
            'can' => $blog instanceof Blog
                ? $this->permissions->forBlog($user, $blog)
                : $this->permissions->forWorkspace($user, $workspace),
        ]);

        return $next($request);
    }
}
