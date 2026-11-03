<?php

namespace Tests\Feature\Themes;

use App\Enums\WorkspaceRole;
use App\Models\BlogTheme;
use App\Models\OutboxEvent;
use App\Models\Theme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsTenants;
use Tests\TestCase;

class BlogThemesTest extends TestCase
{
    use BuildsTenants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedReferenceData();
    }

    public function test_install_activate_and_configure_emit_theme_updated(): void
    {
        [$owner, $workspace, $blog] = $this->tenant();
        $theme = Theme::factory()->create();
        $base = "/{$workspace->slug}/{$blog->slug}/settings/themes";

        $this->actingAs($owner)->get($base)->assertOk()
            ->assertInertia(fn ($page) => $page->component('blog-settings/themes')->where('catalog.0.installed', false));

        $this->actingAs($owner)->post($base, ['theme_id' => $theme->id])->assertRedirect();
        $installed = BlogTheme::query()->where('blog_id', $blog->id)->sole();
        $this->assertSame('#2563eb', $installed->config['primary_color']);
        $this->assertSame(0, OutboxEvent::query()->where('type', 'theme.updated')->count());

        $this->actingAs($owner)->post("{$base}/{$installed->id}/activate")->assertRedirect();
        $this->actingAs($owner)->put("{$base}/{$installed->id}", ['config' => ['primary_color' => '#ff0000', 'show_author' => false, 'unknown' => 'x']])
            ->assertSessionHasNoErrors();

        $events = OutboxEvent::query()->where('blog_id', $blog->id)->where('type', 'theme.updated')->orderBy('sequence')->get();
        $this->assertCount(2, $events);
        $config = $events[1]->payload['theme']['config'];
        $this->assertSame('#ff0000', $config['primary_color']);
        $this->assertFalse($config['show_author']);
        $this->assertArrayNotHasKey('unknown', $config);
    }

    public function test_invalid_values_are_rejected(): void
    {
        [$owner, $workspace, $blog] = $this->tenant();
        $installed = BlogTheme::factory()->for($blog)->create();

        $this->actingAs($owner)->put("/{$workspace->slug}/{$blog->slug}/settings/themes/{$installed->id}", ['config' => ['primary_color' => 'red', 'home_layout' => 'carousel']])
            ->assertSessionHasErrors(['config.primary_color', 'config.home_layout']);
    }

    public function test_only_one_theme_is_active_and_the_active_one_cannot_be_removed(): void
    {
        [$owner, $workspace, $blog] = $this->tenant();
        $first = BlogTheme::factory()->active()->for($blog)->create();
        $second = BlogTheme::factory()->for($blog)->create();
        $base = "/{$workspace->slug}/{$blog->slug}/settings/themes";

        $this->actingAs($owner)->post("{$base}/{$second->id}/activate");
        $this->assertFalse($first->refresh()->is_active);
        $this->assertTrue($second->refresh()->is_active);

        $this->actingAs($owner)->delete("{$base}/{$second->id}")->assertSessionHasErrors('theme');
        $this->actingAs($owner)->delete("{$base}/{$first->id}")->assertSessionHasNoErrors();
        $this->assertModelMissing($first);
    }

    public function test_upgrade_keeps_valid_settings(): void
    {
        [$owner, $workspace, $blog] = $this->tenant();
        $theme = Theme::factory()->create(['latest_version' => '1.0.0']);
        $installed = BlogTheme::factory()->active()->for($blog)->for($theme)->create(['config' => ['primary_color' => '#123456', 'font' => 'serif']]);
        $theme->update(['latest_version' => '1.1.0']);

        $this->actingAs($owner)->post("/{$workspace->slug}/{$blog->slug}/settings/themes/{$installed->id}/upgrade")->assertRedirect();

        $installed->refresh();
        $this->assertSame('1.1.0', $installed->version);
        $this->assertSame('#123456', $installed->config['primary_color']);
        $this->assertSame('list', $installed->config['home_layout']);
        $this->assertSame('1.1.0', OutboxEvent::query()->where('type', 'theme.updated')->latest('sequence')->value('payload')['theme']['version']);
    }

    public function test_premium_themes_and_other_blogs_are_refused(): void
    {
        [$owner, $workspace, $blog] = $this->tenant();
        $premium = Theme::factory()->premium()->create();
        $base = "/{$workspace->slug}/{$blog->slug}/settings/themes";

        $this->actingAs($owner)->post($base, ['theme_id' => $premium->id])->assertSessionHasErrors('theme_id');

        $foreign = BlogTheme::factory()->create();
        $this->actingAs($owner)->post("{$base}/{$foreign->id}/activate")->assertNotFound();

        $viewer = $this->member($workspace, WorkspaceRole::Member, $blog, 'author');
        $this->actingAs($viewer)->get($base)->assertForbidden();
    }
}
