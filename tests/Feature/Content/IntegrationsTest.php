<?php

namespace Tests\Feature\Content;

use App\Enums\IntegrationScope;
use App\Enums\WorkspaceRole;
use App\Integrations\EffectiveScripts;
use App\Models\Article;
use App\Models\AuthorProfile;
use App\Models\Integration;
use App\Models\OutboxEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsTenants;
use Tests\TestCase;

class IntegrationsTest extends TestCase
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
    private function ga4(array $overrides = []): array
    {
        return [
            'provider' => 'ga4', 'scope' => 'blog', 'placement' => 'head', 'consent_category' => 'analytics',
            'page_rule' => 'all', 'enabled' => true, 'config' => ['measurement_id' => 'G-ABC123'],
            ...$overrides,
        ];
    }

    public function test_saving_validates_the_provider_config_and_emits_the_scope_list(): void
    {
        [$owner, $workspace, $blog] = $this->tenant();
        $base = "/{$workspace->slug}/{$blog->slug}/integrations";

        $this->actingAs($owner)->post($base, $this->ga4(['config' => ['measurement_id' => 'UA-123']]))->assertSessionHasErrors('config.measurement_id');
        $this->actingAs($owner)->post($base, $this->ga4(['config' => ['measurement_id' => 'G-ABC123', 'evil' => 'x']]))->assertSessionHasNoErrors();

        $integration = Integration::query()->sole();
        $this->assertSame(['measurement_id' => 'G-ABC123'], $integration->config, 'Unknown config keys are dropped.');

        $event = OutboxEvent::query()->whereBelongsTo($blog)->sole();
        $this->assertSame('integrations.updated', $event->type);
        $this->assertSame('blog', $event->payload['scope']);
        $this->assertCount(1, $event->payload['integrations']);

        $this->actingAs($owner)->post("{$base}/{$integration->id}/toggle")->assertRedirect();
        $this->assertSame([], OutboxEvent::query()->whereBelongsTo($blog)->orderByDesc('sequence')->first()?->payload['integrations']);
    }

    public function test_author_and_article_scopes_need_a_target_of_the_blog(): void
    {
        [$owner, $workspace, $blog] = $this->tenant();
        $base = "/{$workspace->slug}/{$blog->slug}/integrations";
        $foreign = AuthorProfile::factory()->create();

        $this->actingAs($owner)->post($base, $this->ga4(['scope' => 'author']))->assertSessionHasErrors('author_profile_id');
        $this->actingAs($owner)->post($base, $this->ga4(['scope' => 'author', 'author_profile_id' => $foreign->id]))->assertSessionHasErrors('author_profile_id');
        $this->actingAs($owner)->post($base, $this->ga4(['scope' => 'blog', 'author_profile_id' => AuthorProfile::factory()->for($blog)->create()->id]))
            ->assertSessionHasErrors('author_profile_id');
    }

    public function test_custom_scripts_need_raw_permission_password_and_blog_scope(): void
    {
        [$owner, $workspace, $blog] = $this->tenant();
        $admin = $this->member($workspace, WorkspaceRole::Admin);
        $editor = $this->member($workspace, WorkspaceRole::Member, $blog, 'editor');
        $base = "/{$workspace->slug}/{$blog->slug}/integrations";
        $custom = ['provider' => 'custom', 'scope' => 'blog', 'placement' => 'body_end', 'consent_category' => 'functional', 'page_rule' => 'all', 'config' => ['name' => 'Chat', 'html' => '<script>chat()</script>']];

        $this->actingAs($editor)->post($base, $custom)->assertForbidden();
        $this->actingAs($admin)->post($base, $custom)->assertSessionHasErrors('password');
        $this->actingAs($admin)->post($base, [...$custom, 'scope' => 'article', 'article_id' => Article::factory()->for($blog)->create()->id, 'password' => 'password'])
            ->assertSessionHasErrors('scope');

        $this->actingAs($admin)->post($base, [...$custom, 'password' => 'password'])->assertSessionHasNoErrors();
        $this->assertSame(1, Integration::query()->count());
    }

    public function test_effective_scripts_dedupe_and_add_article_dimensions(): void
    {
        [, , $blog] = $this->tenant();
        $article = Article::factory()->for($blog)->create();
        $blogTag = Integration::factory()->for($blog)->create(['config' => ['measurement_id' => 'G-SHARED']]);
        Integration::factory()->forArticle($article)->create(['config' => ['measurement_id' => 'G-SHARED']]);
        Integration::factory()->forAuthor($article->authorProfile()->firstOrFail())->create(['config' => ['measurement_id' => 'G-AUTHOR']]);
        Integration::factory()->for($blog)->create(['config' => ['measurement_id' => 'G-HOMEONLY'], 'page_rule' => ['only' => 'home']]);

        $integrations = EffectiveScripts::integrations($blog, $article);
        $this->assertCount(2, $integrations);
        $this->assertSame(IntegrationScope::Article, $integrations->first()?->scope, 'The most specific scope wins.');
        $this->assertNotContains($blogTag->id, $integrations->pluck('id'));

        $html = EffectiveScripts::render($blog, $article)['head'];
        $this->assertSame(1, substr_count($html, 'gtag/js'), 'A single loader.');
        $this->assertStringContainsString('"article_id":"'.$article->id.'"', $html);
        $this->assertStringContainsString('G-AUTHOR', $html);
        $this->assertStringNotContainsString('G-HOMEONLY', $html);

        $this->assertStringContainsString('G-HOMEONLY', EffectiveScripts::render($blog)['head']);
    }

    public function test_page_shows_preview_for_an_article(): void
    {
        [$owner, $workspace, $blog] = $this->tenant();
        $article = Article::factory()->for($blog)->create();
        Integration::factory()->for($blog)->create();

        $this->actingAs($owner)->get("/{$workspace->slug}/{$blog->slug}/integrations?article={$article->id}")
            ->assertInertia(fn ($page) => $page->component('content/integrations')->where('preview.article', $article->id)->has('integrations', 1));
    }
}
