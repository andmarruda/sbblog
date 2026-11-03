<?php

namespace Tests\Feature\Tenancy;

use App\Enums\CommentPolicy;
use App\Enums\WorkspaceRole;
use App\Models\AuditLog;
use App\Models\Language;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsTenants;
use Tests\TestCase;

class BlogSettingsTest extends TestCase
{
    use BuildsTenants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedReferenceData();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        $pt = Language::query()->where('code', 'pt-BR')->value('id');
        $en = Language::query()->where('code', 'en-US')->value('id');

        return [
            'name' => 'Blog', 'slug' => 'blog', 'title' => 'Title', 'slogan' => null, 'description' => null,
            'canonical_url' => 'https://blog.example.com', 'default_language_id' => $pt,
            'language_ids' => [$pt, $en], 'comment_policy' => 'open', 'autoconvert_webp' => false,
            ...$overrides,
        ];
    }

    public function test_settings_can_be_updated(): void
    {
        [$owner, $workspace, $blog] = $this->tenant();

        $this->actingAs($owner)->patch("/{$workspace->slug}/{$blog->slug}/settings/general", $this->payload())
            ->assertRedirect("/{$workspace->slug}/blog/settings/general")
            ->assertSessionHasNoErrors();

        $blog->refresh();
        $this->assertSame(CommentPolicy::Open, $blog->comment_policy);
        $this->assertFalse($blog->autoconvert_webp);
        $this->assertCount(2, $blog->languages);
        $this->assertTrue(AuditLog::query()->where('action', 'blog.updated')->where('blog_id', $blog->id)->exists());
    }

    public function test_default_language_must_be_enabled(): void
    {
        [$owner, $workspace, $blog] = $this->tenant();
        $en = Language::query()->where('code', 'en-US')->value('id');

        $this->actingAs($owner)->patch("/{$workspace->slug}/{$blog->slug}/settings/general", $this->payload([
            'language_ids' => [$en],
        ]))->assertSessionHasErrors('default_language_id');
    }

    public function test_editors_cannot_change_settings(): void
    {
        [, $workspace, $blog] = $this->tenant();
        $editor = $this->member($workspace, WorkspaceRole::Member, $blog, 'editor');

        $this->actingAs($editor)->get("/{$workspace->slug}/{$blog->slug}/settings/general")->assertForbidden();
        $this->actingAs($editor)->patch("/{$workspace->slug}/{$blog->slug}/settings/general", $this->payload())->assertForbidden();
    }

    public function test_audit_log_is_scoped_to_the_blog(): void
    {
        [$owner, $workspace, $blog] = $this->tenant();
        AuditLog::factory()->create(['workspace_id' => $workspace->id, 'blog_id' => $blog->id, 'action' => 'blog.updated']);
        AuditLog::factory()->create(['workspace_id' => $workspace->id, 'blog_id' => null, 'action' => 'workspace.updated']);
        AuditLog::factory()->create(['action' => 'other.blog']);

        $this->actingAs($owner)->get("/{$workspace->slug}/{$blog->slug}/settings/audit")
            ->assertInertia(fn (Assert $page) => $page->has('logs.data', 1)->where('logs.data.0.action', 'blog.updated'));

        $this->actingAs($owner)->get("/{$workspace->slug}/settings/audit?search=workspace")
            ->assertInertia(fn (Assert $page) => $page->has('logs.data', 1)->where('logs.data.0.action', 'workspace.updated'));
    }
}
