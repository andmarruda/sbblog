<?php

namespace Tests\Feature\Tenancy;

use App\Enums\WorkspaceRole;
use App\Models\Blog;
use App\Models\Language;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsTenants;
use Tests\TestCase;

class TenantAccessTest extends TestCase
{
    use BuildsTenants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedReferenceData();
    }

    public function test_blog_dashboard_shares_the_tenant(): void
    {
        [$owner, $workspace, $blog] = $this->tenant();

        $this->actingAs($owner)->get("/{$workspace->slug}/{$blog->slug}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('blogs/dashboard')
                ->where('currentWorkspace.slug', $workspace->slug)
                ->where('currentWorkspace.role', 'owner')
                ->where('currentBlog.slug', $blog->slug)
                ->has('tenants', 1)
                ->where('can', fn ($can) => collect($can)->contains('articles.publish')));
    }

    public function test_outsiders_get_a_404(): void
    {
        [, $workspace, $blog] = $this->tenant();

        $this->actingAs(User::factory()->create())->get("/{$workspace->slug}/{$blog->slug}")->assertNotFound();
        $this->actingAs(User::factory()->create())->get("/{$workspace->slug}/settings/blogs")->assertNotFound();
    }

    public function test_members_only_open_their_blogs(): void
    {
        [, $workspace, $blog] = $this->tenant();
        $other = Blog::factory()->for($workspace)->create();
        $author = $this->member($workspace, WorkspaceRole::Member, $blog, 'author');

        $this->actingAs($author)->get("/{$workspace->slug}/{$blog->slug}")->assertOk();
        $this->actingAs($author)->get("/{$workspace->slug}/{$other->slug}")->assertNotFound();
    }

    public function test_blogs_are_scoped_to_their_workspace(): void
    {
        [$owner, $workspace] = $this->tenant();
        [, , $foreignBlog] = $this->tenant();

        $this->actingAs($owner)->get("/{$workspace->slug}/{$foreignBlog->slug}")->assertNotFound();
    }

    public function test_deleted_workspaces_and_blogs_are_gone(): void
    {
        [$owner, $workspace, $blog] = $this->tenant();

        $blog->delete();
        $this->actingAs($owner)->get("/{$workspace->slug}/{$blog->slug}")->assertNotFound();

        $workspace->delete();
        $this->actingAs($owner)->get("/{$workspace->slug}/settings/blogs")->assertNotFound();
    }

    public function test_opening_a_blog_remembers_it(): void
    {
        [$owner, $workspace, $blog] = $this->tenant();
        $second = Blog::factory()->for($workspace)->create();

        $this->actingAs($owner)->get("/{$workspace->slug}/{$second->slug}");

        $this->assertSame($second->id, $owner->refresh()->preference('last_blog_id'));
        $this->actingAs($owner)->get('/dashboard')->assertRedirect("/{$workspace->slug}/{$second->slug}");
    }

    public function test_dashboard_sends_new_users_to_onboarding(): void
    {
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertRedirect('/onboarding');
    }

    public function test_dashboard_falls_back_to_the_first_blog(): void
    {
        [$owner, $workspace, $blog] = $this->tenant();

        $this->actingAs($owner)->get('/dashboard')->assertRedirect("/{$workspace->slug}/{$blog->slug}");
    }

    public function test_member_without_blogs_lands_on_the_workspace(): void
    {
        [, $workspace] = $this->tenant();
        $member = $this->member($workspace);

        $this->actingAs($member)->get('/dashboard')->assertRedirect("/{$workspace->slug}/settings/blogs");
    }

    public function test_onboarding_creates_a_workspace_and_a_blog(): void
    {
        $user = User::factory()->create();
        $language = Language::query()->where('code', 'pt-BR')->value('id');

        $this->actingAs($user)->post('/onboarding', [
            'workspace_name' => 'Acme',
            'blog_name' => 'Acme Blog',
            'language_id' => $language,
        ])->assertRedirect('/acme/acme-blog');

        $workspace = Workspace::query()->where('slug', 'acme')->sole();
        $this->assertSame($user->id, $workspace->owner_user_id);
        $this->assertSame(WorkspaceRole::Owner, $workspace->members()->first()?->pivot?->getAttribute('workspace_role'));
        $this->assertSame([$language], $workspace->blogs()->sole()->languages()->pluck('languages.id')->all());
    }

    public function test_onboarding_avoids_reserved_and_taken_slugs(): void
    {
        $language = Language::query()->where('code', 'pt-BR')->value('id');

        $this->actingAs(User::factory()->create())->post('/onboarding', [
            'workspace_name' => 'Login', 'blog_name' => 'B', 'language_id' => $language,
        ]);
        $this->actingAs(User::factory()->create())->post('/onboarding', [
            'workspace_name' => 'Login', 'blog_name' => 'B', 'language_id' => $language,
        ]);

        $this->assertEqualsCanonicalizing(['login-2', 'login-3'], Workspace::query()->pluck('slug')->all());
    }

    public function test_onboarding_is_skipped_for_existing_members(): void
    {
        [$owner] = $this->tenant();

        $this->actingAs($owner)->get('/onboarding')->assertRedirect('/dashboard');
    }
}
