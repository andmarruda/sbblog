<?php

namespace App\Authorization;

use App\Enums\TwoFactorPolicy;
use App\Models\Blog;
use App\Models\User;
use App\Models\Workspace;

/**
 * 2FA is required when any of these is true (ERD §2): the workspace policy is
 * `required_all`, the user's role has `requires_two_factor` (under
 * `required_by_role` or stricter), or the permission itself has
 * `requires_two_factor` — the last one applies even when the policy is `off`.
 */
class TwoFactorRequirement
{
    public function __construct(private readonly PermissionResolver $permissions) {}

    public function enforced(): bool
    {
        return (bool) config('sbblog.two_factor.enforce');
    }

    public function requiredFor(User $user, Workspace|Blog $scope, ?string $permission = null): bool
    {
        $workspace = $scope instanceof Blog ? $scope->workspace : $scope;

        if ($workspace === null) {
            return false;
        }

        if ($workspace->two_factor_policy === TwoFactorPolicy::RequiredAll) {
            return true;
        }

        if ($permission !== null && Permissions::requiresTwoFactor($permission)) {
            return true;
        }

        if ($workspace->two_factor_policy === TwoFactorPolicy::RequiredByRole && $scope instanceof Blog) {
            return (bool) $this->permissions->blogRole($user, $scope)?->requires_two_factor;
        }

        return false;
    }

    /**
     * True when the user must set up 2FA before doing this.
     */
    public function blocks(User $user, Workspace|Blog $scope, ?string $permission = null): bool
    {
        return $this->enforced()
            && ! $user->hasTwoFactorEnabled()
            && $this->requiredFor($user, $scope, $permission);
    }
}
