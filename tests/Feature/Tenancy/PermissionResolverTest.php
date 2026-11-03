<?php

namespace Tests\Feature\Tenancy;

use App\Authorization\PermissionResolver;
use App\Authorization\Permissions;
use App\Enums\TwoFactorPolicy;
use App\Enums\WorkspaceRole;
use App\Models\Blog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\Concerns\BuildsTenants;
use Tests\TestCase;

class PermissionResolverTest extends TestCase
{
    use BuildsTenants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedReferenceData();
    }

    public function test_owner_can_do_everything_on_every_blog(): void
    {
        [$owner, $workspace, $blog] = $this->tenant();

        $this->assertEqualsCanonicalizing(array_keys(Permissions::ALL), app(PermissionResolver::class)->forBlog($owner, $blog));
        $this->assertTrue(Gate::forUser($owner)->allows('blogs.delete', $workspace));
    }

    public function test_admin_gets_the_admin_role_on_every_blog(): void
    {
        [, $workspace, $blog] = $this->tenant();
        $admin = $this->member($workspace, WorkspaceRole::Admin);

        $this->assertTrue(Gate::forUser($admin)->allows('articles.publish', $blog));
        $this->assertTrue(Gate::forUser($admin)->allows('members.manage', $workspace));
        $this->assertFalse(Gate::forUser($admin)->allows('workspace.billing', $workspace));
        $this->assertFalse(Gate::forUser($admin)->allows('blogs.delete', $workspace));
    }

    public function test_member_only_gets_their_blog_role(): void
    {
        [, $workspace, $blog] = $this->tenant();
        $other = Blog::factory()->for($workspace)->create();
        $author = $this->member($workspace, WorkspaceRole::Member, $blog, 'author');

        $this->assertTrue(Gate::forUser($author)->allows('articles.create', $blog));
        $this->assertFalse(Gate::forUser($author)->allows('articles.publish', $blog));
        $this->assertFalse(Gate::forUser($author)->allows('articles.create', $other));
        $this->assertFalse(Gate::forUser($author)->allows('members.manage', $workspace));
        $this->assertFalse(app(PermissionResolver::class)->canAccessBlog($author, $other));
    }

    public function test_outsiders_get_nothing(): void
    {
        [, $workspace, $blog] = $this->tenant();
        $stranger = User::factory()->create();

        $this->assertSame([], app(PermissionResolver::class)->forBlog($stranger, $blog));
        $this->assertFalse(Gate::forUser($stranger)->allows('articles.view', $blog));
        $this->assertFalse(Gate::forUser($stranger)->allows('workspace.settings', $workspace));
    }

    public function test_permission_abilities_need_a_scope(): void
    {
        [$owner] = $this->tenant();

        $this->assertFalse(Gate::forUser($owner)->allows('articles.view'));
    }

    public function test_two_factor_is_not_enforced_by_default(): void
    {
        [$owner, $workspace] = $this->tenant();

        $this->assertTrue(Gate::forUser($owner)->allows('members.manage', $workspace));
    }

    public function test_two_factor_permissions_are_blocked_when_enforced(): void
    {
        config(['sbblog.two_factor.enforce' => true]);
        [$owner, $workspace, $blog] = $this->tenant();

        // members.manage requires 2FA even with the policy off.
        $this->assertFalse(Gate::forUser($owner)->allows('members.manage', $workspace));
        $this->assertTrue(Gate::forUser($owner)->allows('articles.publish', $blog));

        $owner->forceFill(['two_factor_secret' => 'JBSWY3DPEHPK3PXP', 'two_factor_confirmed_at' => now()])->save();
        app()->forgetScopedInstances();

        $this->assertTrue(Gate::forUser($owner)->allows('members.manage', $workspace));
    }

    public function test_required_all_policy_sends_users_without_two_factor_to_setup(): void
    {
        config(['sbblog.two_factor.enforce' => true]);
        [$owner, $workspace, $blog] = $this->tenant();
        $workspace->update(['two_factor_policy' => TwoFactorPolicy::RequiredAll]);

        $this->actingAs($owner)
            ->get("/{$workspace->slug}/{$blog->slug}")
            ->assertRedirect('/account/security');

        $owner->forceFill(['two_factor_secret' => 'JBSWY3DPEHPK3PXP', 'two_factor_confirmed_at' => now()])->save();

        $this->actingAs($owner)->get("/{$workspace->slug}/{$blog->slug}")->assertOk();
    }
}
