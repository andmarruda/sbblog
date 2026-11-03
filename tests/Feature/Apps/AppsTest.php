<?php

namespace Tests\Feature\Apps;

use App\Enums\ArticleStatus;
use App\Enums\SuggestionSource;
use App\Enums\WorkspaceRole;
use App\Models\App;
use App\Models\AppInstallation;
use App\Models\Article;
use App\Models\ArticleSuggestion;
use App\Models\Blog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\BuildsTenants;
use Tests\TestCase;

class AppsTest extends TestCase
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
    private function body(string $text = 'From the API'): array
    {
        return ['title' => 'API title', 'description' => null, 'cover_media_id' => null, 'body' => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $text]]]]]];
    }

    /**
     * Run the whole OAuth dance and return the access token.
     *
     * @param  list<string>  $scopes
     */
    private function connect(User $user, Blog $blog, App $app, array $scopes): string
    {
        $query = ['client_id' => $app->client_id, 'redirect_uri' => 'https://app.test/callback', 'scope' => implode(' ', $scopes), 'state' => 'xyz'];

        $this->actingAs($user)->get('/oauth/authorize?'.http_build_query($query))->assertOk()
            ->assertInertia(fn ($page) => $page->component('oauth/authorize')->has('blogs'));

        $location = $this->actingAs($user)->post('/oauth/authorize', [...$query, 'approve' => true, 'blog_id' => $blog->id])->headers->get('X-Inertia-Location')
            ?? $this->actingAs($user)->post('/oauth/authorize', [...$query, 'approve' => true, 'blog_id' => $blog->id])->headers->get('Location');

        parse_str((string) parse_url((string) $location, PHP_URL_QUERY), $params);
        $this->assertSame('xyz', $params['state']);

        // Forget the session login from the consent step: API calls use the token alone.
        $token = $this->postJson('/api/oauth/token', [
            'grant_type' => 'authorization_code', 'code' => $params['code'], 'client_id' => $app->client_id,
            'client_secret' => 'secret', 'redirect_uri' => 'https://app.test/callback',
        ])->assertOk()->assertJsonPath('blog_id', $blog->id)->json('access_token');
        app('auth')->forgetGuards();

        return $token;
    }

    private function app(array $scopes = ['articles:read', 'articles:write', 'media:write', 'taxonomy:read']): App
    {
        return App::factory()->create([
            'client_secret_hash' => Hash::make('secret'),
            'redirect_uris' => ['https://app.test/callback'],
            'requested_scopes' => $scopes,
        ]);
    }

    public function test_the_full_install_flow_gives_a_blog_bound_token(): void
    {
        [$owner, $workspace, $blog] = $this->tenant();
        $other = Blog::factory()->for($workspace)->create();
        $article = Article::factory()->for($blog)->create();
        $foreign = Article::factory()->for($other)->create();
        $token = $this->connect($owner, $blog, $this->app(), ['articles:read', 'articles:write']);

        $this->withToken($token)->getJson('/api/v1/me')->assertJsonPath('type', 'app')->assertJsonPath('blog_id', $blog->id);
        $this->withToken($token)->getJson("/api/v1/blogs/{$blog->id}/articles")->assertOk()->assertJsonPath('total', 1);
        $this->withToken($token)->getJson("/api/v1/blogs/{$other->id}/articles")->assertNotFound();
        $this->withToken($token)->getJson("/api/v1/blogs/{$blog->id}/articles/{$foreign->id}")->assertNotFound();
        $this->withToken($token)->getJson("/api/v1/blogs/{$blog->id}/taxonomy")->assertForbidden();

        $this->withToken($token)->putJson("/api/v1/blogs/{$blog->id}/articles/{$article->id}/draft", $this->body())->assertCreated();
        $this->assertNotNull($article->refresh()->currentRevision?->created_by_app_installation_id);
        $this->assertSame(ArticleStatus::Draft, $article->status, 'Apps never publish.');

        $this->withToken($token)->postJson("/api/v1/blogs/{$blog->id}/articles/{$article->id}/suggestions", $this->body('Better'))->assertCreated();
        $this->assertSame(SuggestionSource::App, ArticleSuggestion::query()->sole()->source);
    }

    public function test_codes_work_once_and_need_the_right_secret_and_redirect(): void
    {
        [$owner, , $blog] = $this->tenant();
        $app = $this->app();
        $query = ['client_id' => $app->client_id, 'redirect_uri' => 'https://app.test/callback'];

        $this->actingAs($owner)->get('/oauth/authorize?'.http_build_query([...$query, 'redirect_uri' => 'https://evil.test/cb']))->assertStatus(400);
        $this->actingAs($owner)->get('/oauth/authorize?'.http_build_query([...$query, 'scope' => 'articles:read admin']))->assertStatus(400);

        $response = $this->actingAs($owner)->post('/oauth/authorize', [...$query, 'approve' => true, 'blog_id' => $blog->id]);
        parse_str((string) parse_url((string) ($response->headers->get('X-Inertia-Location') ?? $response->headers->get('Location')), PHP_URL_QUERY), $params);
        $exchange = ['grant_type' => 'authorization_code', 'code' => $params['code'], 'client_id' => $app->client_id, 'redirect_uri' => 'https://app.test/callback'];

        $this->postJson('/api/oauth/token', [...$exchange, 'client_secret' => 'wrong'])->assertUnauthorized();
        $this->postJson('/api/oauth/token', [...$exchange, 'client_secret' => 'secret'])->assertOk();
        $this->postJson('/api/oauth/token', [...$exchange, 'client_secret' => 'secret'])->assertStatus(400)->assertJsonPath('error', 'invalid_grant');
    }

    public function test_denying_redirects_with_an_error(): void
    {
        [$owner] = $this->tenant();
        $app = $this->app();

        $response = $this->actingAs($owner)->post('/oauth/authorize', ['client_id' => $app->client_id, 'redirect_uri' => 'https://app.test/callback', 'state' => 's', 'approve' => false]);

        $this->assertStringContainsString('error=access_denied', (string) ($response->headers->get('X-Inertia-Location') ?? $response->headers->get('Location')));
        $this->assertSame(0, AppInstallation::query()->count());
    }

    public function test_only_people_with_apps_install_can_install(): void
    {
        [, $workspace, $blog] = $this->tenant();
        $editor = $this->member($workspace, WorkspaceRole::Member, $blog, 'editor');
        $app = $this->app();

        $this->actingAs($editor)->post('/oauth/authorize', ['client_id' => $app->client_id, 'redirect_uri' => 'https://app.test/callback', 'approve' => true, 'blog_id' => $blog->id])
            ->assertForbidden();
    }

    public function test_revoking_an_installation_kills_its_token(): void
    {
        [$owner, $workspace, $blog] = $this->tenant();
        $token = $this->connect($owner, $blog, $this->app(), ['articles:read']);
        $installation = AppInstallation::query()->sole();

        $this->actingAs($owner)->delete("/{$workspace->slug}/{$blog->slug}/settings/apps/{$installation->id}")->assertSessionHasNoErrors();
        app('auth')->forgetGuards();

        $this->withToken($token)->getJson("/api/v1/blogs/{$blog->id}/articles")->assertUnauthorized();
    }

    public function test_personal_tokens_are_limited_by_scope_and_permissions(): void
    {
        [$owner, $workspace, $blog] = $this->tenant();
        $viewer = $this->member($workspace, WorkspaceRole::Member, $blog, 'viewer');
        $article = Article::factory()->for($blog)->create();

        $this->actingAs($viewer)->post('/account/tokens', ['name' => 'CLI', 'abilities' => ['articles:read', 'articles:write'], 'password' => 'password'])
            ->assertSessionHas('new_personal_token');
        $token = session('new_personal_token');
        app('auth')->forgetGuards();

        $this->withToken($token)->getJson("/api/v1/blogs/{$blog->id}/articles")->assertOk();
        $this->withToken($token)->putJson("/api/v1/blogs/{$blog->id}/articles/{$article->id}/draft", $this->body())->assertForbidden();
        $this->withToken($token)->getJson('/api/v1/blogs/'.Blog::factory()->create()->id.'/articles')->assertNotFound();

        $ownerToken = $owner->createToken('x', ['articles:read'])->plainTextToken;
        app('auth')->forgetGuards();
        $this->withToken($ownerToken)->putJson("/api/v1/blogs/{$blog->id}/articles/{$article->id}/draft", $this->body())->assertForbidden();
    }

    public function test_workspace_apps_are_managed_with_a_one_time_secret(): void
    {
        [$owner, $workspace] = $this->tenant();

        $this->actingAs($owner)->post("/{$workspace->slug}/settings/apps", [
            'name' => 'Writer', 'redirect_uris_text' => "https://w.test/cb\nhttps://w.test/cb2", 'requested_scopes' => ['articles:read'],
        ])->assertSessionHas('new_client_secret');

        $app = App::query()->sole();
        $this->assertTrue(Hash::check(session('new_client_secret')['secret'], $app->client_secret_hash));
        $this->assertSame(['https://w.test/cb', 'https://w.test/cb2'], $app->redirect_uris);

        $this->actingAs($owner)->delete("/{$workspace->slug}/settings/apps/{$app->id}")->assertSessionHasNoErrors();
        $this->assertModelMissing($app);
    }
}
