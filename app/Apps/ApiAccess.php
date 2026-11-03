<?php

namespace App\Apps;

use App\Models\AppInstallation;
use App\Models\Blog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Who is calling the API and whether they may touch a blog. Both checks always apply:
 * the token must carry the scope, and the caller must be allowed on that blog — an app only on
 * the blog it was installed on, a person through their own permissions.
 */
class ApiAccess
{
    public static function actor(Request $request): User|AppInstallation
    {
        // The sanctum guard resolves either a person or an app installation.
        $actor = Auth::guard('sanctum')->user();
        abort_unless($actor instanceof User || $actor instanceof AppInstallation, 401);

        return $actor;
    }

    /**
     * @param  string|null  $permission  Permission key a person needs on the blog.
     */
    public static function authorize(Request $request, Blog $blog, string $scope, ?string $permission = null): User|AppInstallation
    {
        $actor = self::actor($request);
        $token = $actor->currentAccessToken();

        abort_unless($token->can($scope), 403, "This token is missing the {$scope} scope.");
        abort_if($blog->trashed() || $blog->workspace()->doesntExist(), 404);

        if ($actor instanceof AppInstallation) {
            // An installation reaches its own blog only, and not after being revoked.
            abort_if($actor->revoked_at !== null || $actor->blog_id !== $blog->id, 404);
        } elseif ($permission !== null) {
            abort_unless(Gate::forUser($actor)->allows($permission, $blog), Gate::forUser($actor)->allows('articles.view', $blog) ? 403 : 404);
        }

        return $actor;
    }
}
