<?php

namespace Tests\Feature\Tenancy;

use App\Enums\WorkspaceRole;
use App\Models\Blog;
use App\Models\Language;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsTenants;
use Tests\TestCase;

class WorkspaceSettingsTest extends TestCase
{
    use BuildsTenants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedReferenceData();
    }

    public function test_admins_can_update_the_workspace(): void
    {
        [, $workspace] = $this->tenant();
        $admin = $this->member($workspace, WorkspaceRole::Admin);

        $this->actingAs($admin)->patch("/{$workspace->slug}/settings/general", [
            'name' => 'Renamed',
            'slug' => 'renamed',
            'two_factor_policy' => 'optional',
        ])->assertRedirect('/renamed/settings/general')->assertSessionHasNoErrors();

        $this->assertSame('Renamed', $workspace->refresh()->name);
    }

    public function test_reserved_slugs_are_rejected(): void
    {
        [$owner, $workspace] = $this->tenant();

        $this->actingAs($owner)->patch("/{$workspace->slug}/settings/general", [
            'name' => 'X', 'slug' => 'dashboard', 'two_factor_policy' => 'off',
        ])->assertSessionHasErrors('slug');
    }

    public function test_members_cannot_change_settings(): void
    {
        [, $workspace, $blog] = $this->tenant();
        $author = $this->member($workspace, WorkspaceRole::Member, $blog, 'author');

        $this->actingAs($author)->get("/{$workspace->slug}/settings/general")->assertForbidden();
        $this->actingAs($author)->get("/{$workspace->slug}/settings/members")->assertForbidden();
        $this->actingAs($author)->get("/{$workspace->slug}/settings/blogs")->assertOk();
    }

    public function test_only_the_owner_deletes_the_workspace_with_confirmation(): void
    {
        [$owner, $workspace] = $this->tenant();
        $admin = $this->member($workspace, WorkspaceRole::Admin);

        $this->actingAs($admin)->delete("/{$workspace->slug}/settings", [
            'confirmation' => $workspace->slug, 'password' => 'password',
        ])->assertForbidden();

        $this->actingAs($owner)->delete("/{$workspace->slug}/settings", [
            'confirmation' => 'wrong', 'password' => 'password',
        ])->assertSessionHasErrors('confirmation');

        $this->actingAs($owner)->delete("/{$workspace->slug}/settings", [
            'confirmation' => $workspace->slug, 'password' => 'password',
        ])->assertRedirect('/dashboard');

        $this->assertSoftDeleted($workspace);
    }

    public function test_owner_only_actions_respect_two_factor_enforcement(): void
    {
        config(['sbblog.two_factor.enforce' => true]);
        [$owner, $workspace] = $this->tenant();
        $admin = $this->member($workspace, WorkspaceRole::Admin);

        $this->actingAs($owner)->delete("/{$workspace->slug}/settings", ['confirmation' => $workspace->slug, 'password' => 'password'])
            ->assertForbidden();
        $this->actingAs($owner)->post("/{$workspace->slug}/settings/members/{$admin->id}/transfer-ownership", ['password' => 'password'])
            ->assertForbidden();

        $this->assertNotSoftDeleted($workspace);
    }

    public function test_admins_create_blogs_and_members_cannot(): void
    {
        [, $workspace, $blog] = $this->tenant();
        $admin = $this->member($workspace, WorkspaceRole::Admin);
        $editor = $this->member($workspace, WorkspaceRole::Member, $blog, 'editor');
        $language = Language::query()->where('code', 'en-US')->value('id');

        $this->actingAs($editor)->post("/{$workspace->slug}/settings/blogs", [
            'name' => 'News', 'language_id' => $language,
        ])->assertForbidden();

        $this->actingAs($admin)->post("/{$workspace->slug}/settings/blogs", [
            'name' => 'News', 'language_id' => $language,
        ])->assertRedirect("/{$workspace->slug}/news");

        $this->actingAs($admin)->post("/{$workspace->slug}/settings/blogs", [
            'name' => 'News 2', 'slug' => 'news', 'language_id' => $language,
        ])->assertSessionHasErrors('slug');
    }

    public function test_blogs_can_be_deleted_and_restored(): void
    {
        [$owner, $workspace, $blog] = $this->tenant();

        $this->actingAs($owner)->delete("/{$workspace->slug}/{$blog->slug}/settings", ['confirmation' => $blog->slug])
            ->assertRedirect("/{$workspace->slug}/settings/blogs");
        $this->assertSoftDeleted($blog);

        $this->actingAs($owner)->post("/{$workspace->slug}/settings/blogs/{$blog->id}/restore")->assertRedirect();
        $this->assertNotSoftDeleted($blog);
    }

    public function test_admins_cannot_delete_blogs(): void
    {
        [, $workspace, $blog] = $this->tenant();
        $admin = $this->member($workspace, WorkspaceRole::Admin);

        $this->actingAs($admin)->delete("/{$workspace->slug}/{$blog->slug}/settings", ['confirmation' => $blog->slug])
            ->assertForbidden();
    }

    public function test_restore_is_scoped_to_the_workspace(): void
    {
        [$owner, $workspace] = $this->tenant();
        $foreign = Blog::factory()->create();
        $foreign->delete();

        $this->actingAs($owner)->post("/{$workspace->slug}/settings/blogs/{$foreign->id}/restore")->assertNotFound();
    }
}
