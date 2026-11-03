<?php

namespace Tests\Feature;

use App\Actions\Content\ArticleEditor;
use App\Actions\Content\Suggestions;
use App\Jobs\BuildSnapshot;
use App\Models\Article;
use App\Models\EventTransport;
use App\Models\Frontend;
use App\Models\Integration;
use App\Models\Invitation;
use App\Models\Media;
use App\Notifications\WorkspaceInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsTenants;
use Tests\TestCase;

/**
 * Every console page renders its component for an owner with real data, and the actions no
 * other test covers work.
 */
class SmokeTest extends TestCase
{
    use BuildsTenants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedReferenceData();
    }

    public function test_every_page_renders(): void
    {
        [$owner, $workspace, $blog] = $this->tenant();
        $article = app(ArticleEditor::class)->create($blog, $owner, 'Hello');
        app(ArticleEditor::class)->publish($article);
        $suggestion = app(Suggestions::class)->create($article, $owner, ['title' => 'Better', 'description' => null, 'cover_media_id' => null, 'body' => ['type' => 'doc', 'content' => []]]);
        Media::factory()->for($blog)->create();
        Integration::factory()->for($blog)->create();
        Frontend::factory()->for($blog)->create();
        EventTransport::factory()->for($blog)->create();

        $w = "/{$workspace->slug}";
        $b = "/{$workspace->slug}/{$blog->slug}";
        $pages = [
            "{$w}/settings/blogs" => 'workspace-settings/blogs',
            "{$w}/settings/general" => 'workspace-settings/general',
            "{$w}/settings/members" => 'workspace-settings/members',
            "{$w}/settings/roles" => 'workspace-settings/roles',
            "{$w}/settings/audit" => 'workspace-settings/audit',
            $b => 'blogs/dashboard',
            "{$b}/settings/general" => 'blog-settings/general',
            "{$b}/settings/social" => 'blog-settings/social',
            "{$b}/settings/members" => 'blog-settings/members',
            "{$b}/settings/audit" => 'blog-settings/audit',
            "{$b}/articles" => 'content/articles/index',
            "{$b}/articles?trashed=1" => 'content/articles/index',
            "{$b}/articles/{$article->id}" => 'content/articles/edit',
            "{$b}/articles/{$article->id}/preview" => 'content/articles/preview',
            "{$b}/articles/{$article->id}/revisions" => 'content/articles/revisions',
            "{$b}/articles/{$article->id}/suggestions" => 'content/articles/suggestions',
            "{$b}/articles/{$article->id}/suggestions/{$suggestion->id}" => 'content/articles/suggestion',
            "{$b}/media" => 'content/media',
            "{$b}/categories" => 'content/categories',
            "{$b}/tags" => 'content/tags',
            "{$b}/authors" => 'content/authors',
            "{$b}/integrations?article={$article->id}" => 'content/integrations',
            "{$b}/publishing" => 'publishing/index',
            "{$b}/publishing/events" => 'publishing/events',
            '/account/profile' => 'settings/profile',
            '/account/password' => 'settings/password',
            '/account/sessions' => 'settings/sessions',
        ];

        foreach ($pages as $url => $component) {
            $this->actingAs($owner)->get($url)->assertOk()->assertInertia(fn (Assert $page) => $page->component($component));
        }

        $this->actingAs($owner)->getJson("{$b}/media/picker")->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_invitations_can_be_resent_and_revoked(): void
    {
        Notification::fake();
        [$owner, $workspace] = $this->tenant();
        $invitation = Invitation::factory()->for($workspace)->create();
        $hash = $invitation->token_hash;

        $this->actingAs($owner)->post("/{$workspace->slug}/settings/invitations/{$invitation->id}/resend")->assertSessionHasNoErrors();
        $this->assertNotSame($hash, $invitation->refresh()->token_hash, 'A fresh token is issued.');
        Notification::assertSentTo(new AnonymousNotifiable, WorkspaceInvitation::class);

        $this->actingAs($owner)->delete("/{$workspace->slug}/settings/invitations/{$invitation->id}")->assertSessionHasNoErrors();
        $this->assertModelMissing($invitation);
    }

    public function test_integrations_transports_and_frontends_can_be_changed_and_removed(): void
    {
        [$owner, $workspace, $blog] = $this->tenant();
        $b = "/{$workspace->slug}/{$blog->slug}";
        $integration = Integration::factory()->for($blog)->create();
        $transport = EventTransport::factory()->for($blog)->create(['enabled' => true]);
        $frontend = Frontend::factory()->for($blog)->create();

        $this->actingAs($owner)->patch("{$b}/integrations/{$integration->id}", [
            'provider' => 'ga4', 'scope' => 'blog', 'placement' => 'body_end', 'consent_category' => 'analytics',
            'page_rule' => 'articles', 'config' => ['measurement_id' => 'G-NEW123'],
        ])->assertSessionHasNoErrors();
        $this->assertSame(['only' => 'articles'], $integration->refresh()->page_rule);
        $this->actingAs($owner)->delete("{$b}/integrations/{$integration->id}")->assertSessionHasNoErrors();
        $this->assertModelMissing($integration);

        $this->actingAs($owner)->patch("{$b}/publishing/transports/{$transport->id}", ['enabled' => false])->assertSessionHasNoErrors();
        $this->assertFalse($transport->refresh()->enabled);
        $this->actingAs($owner)->delete("{$b}/publishing/transports/{$transport->id}")->assertSessionHasNoErrors();
        $this->assertModelMissing($transport);

        $this->actingAs($owner)->patch("{$b}/publishing/frontends/{$frontend->id}", ['name' => 'Renamed', 'base_url' => 'https://x.test', 'store_adapter' => 'd1'])->assertSessionHasNoErrors();
        $this->assertSame('Renamed', $frontend->refresh()->name);
        $this->actingAs($owner)->delete("{$b}/publishing/frontends/{$frontend->id}")->assertSessionHasNoErrors();
        $this->assertModelMissing($frontend);
    }

    public function test_snapshots_are_queued_only_when_needed(): void
    {
        Queue::fake();
        [, , $blog] = $this->tenant();
        [, , $quiet] = $this->tenant();
        Frontend::factory()->for($blog)->create();
        Frontend::factory()->for($quiet)->create();
        app(ArticleEditor::class)->publish(Article::factory()->for($blog)->create());

        $this->artisan('snapshots:build')->expectsOutput('Queued 1 snapshot(s).');

        Queue::assertPushed(BuildSnapshot::class, fn (BuildSnapshot $job) => $job->blog->is($blog));
    }
}
