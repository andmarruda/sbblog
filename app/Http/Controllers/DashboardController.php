<?php

namespace App\Http\Controllers;

use App\Authorization\PermissionResolver;
use App\Models\Blog;
use App\Models\User;
use App\Support\TenantDirectory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Send the user to the last blog they opened, or the first one they can open.
     * No workspace yet means onboarding.
     */
    public function __invoke(Request $request, PermissionResolver $permissions): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $last = Blog::query()->with('workspace')->find($user->preference('last_blog_id'));

        if ($last instanceof Blog && $last->workspace !== null && $permissions->canAccessBlog($user, $last)) {
            return to_route('blogs.dashboard', [$last->workspace->slug, $last->slug]);
        }

        $tenants = TenantDirectory::for($user);

        if ($tenants === []) {
            return to_route('onboarding');
        }

        foreach ($tenants as $workspace) {
            if ($workspace['blogs'] !== []) {
                return to_route('blogs.dashboard', [$workspace['slug'], $workspace['blogs'][0]['slug']]);
            }
        }

        return to_route('workspaces.settings.blogs', $tenants[0]['slug']);
    }
}
