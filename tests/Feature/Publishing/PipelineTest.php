<?php

namespace Tests\Feature\Publishing;

use App\Actions\Content\ArticleEditor;
use App\Enums\WorkspaceRole;
use App\Jobs\BuildSnapshot;
use App\Jobs\DispatchEvents;
use App\Models\Article;
use App\Models\ArticleRevision;
use App\Models\Blog;
use App\Models\EventTransport;
use App\Models\Frontend;
use App\Models\Media;
use App\Models\Snapshot;
use App\Publishing\SchemaValidator;
use App\Support\SyncToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsTenants;
use Tests\TestCase;

class PipelineTest extends TestCase
{
    use BuildsTenants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedReferenceData();
    }

    /**
     * @return array{Blog, string}
     */
    private function frontendFor(Blog $blog): array
    {
        $token = SyncToken::generate();
        Frontend::factory()->for($blog)->create(['sync_token_hash' => SyncToken::hash($token)]);

        return [$blog, $token];
    }

    private function publishArticles(Blog $blog, int $count): void
    {
        foreach (range(1, $count) as $i) {
            app(ArticleEditor::class)->publish(Article::factory()->for($blog)->create());
        }
    }

    public function test_frontends_pull_events_in_pages_and_ack(): void
    {
        [, , $blog] = $this->tenant();
        [, $token] = $this->frontendFor($blog);
        $this->publishArticles($blog, 3);

        $first = $this->withToken($token)->getJson('/api/sync?limit=2')->assertOk()
            ->assertJsonPath('has_more', true)
            ->assertJsonPath('latest_sequence', 3)
            ->assertJsonCount(2, 'events');

        $envelope = $first->json('events.0');
        $this->assertSame([], app(SchemaValidator::class)->errors(json_decode((string) json_encode($envelope)), 'events/envelope.schema.json'));
        $this->assertSame([], app(SchemaValidator::class)->errors(json_decode((string) json_encode($envelope['payload'])), 'events/article.published.v1.schema.json'));

        $this->withToken($token)->getJson('/api/sync?after=2')->assertJsonPath('has_more', false)->assertJsonPath('events.0.sequence', 3);

        $this->withToken($token)->postJson('/api/sync/ack', ['sequence' => 99])->assertJsonPath('last_acked_sequence', 3);
        $this->withToken($token)->postJson('/api/sync/ack', ['sequence' => 1])->assertJsonPath('last_acked_sequence', 3);
        $this->assertSame(3, Frontend::query()->sole()->last_acked_sequence, 'Acks never move backwards or past the log.');
    }

    public function test_empty_objects_stay_objects(): void
    {
        [, , $blog] = $this->tenant();
        [, $token] = $this->frontendFor($blog);
        $article = Article::factory()->for($blog)->create();
        $cover = Media::factory()->for($blog)->create(['variants' => null]);
        ArticleRevision::query()->whereKey($article->current_revision_id)->toBase()->update(['cover_media_id' => $cover->id]);
        app(ArticleEditor::class)->publish($article);

        $raw = $this->withToken($token)->getJson('/api/sync')->getContent();

        $this->assertStringContainsString('"variants":{}', (string) $raw);
    }

    public function test_bad_tokens_and_other_blogs_are_refused(): void
    {
        [, , $blog] = $this->tenant();
        [, , $other] = $this->tenant();
        [, $token] = $this->frontendFor($blog);
        $this->publishArticles($other, 1);

        $this->getJson('/api/sync')->assertUnauthorized();
        $this->withToken('sbf_wrong')->getJson('/api/sync')->assertUnauthorized();
        $this->withToken($token)->getJson('/api/sync')->assertJsonCount(0, 'events');
    }

    public function test_pruned_history_requires_the_snapshot(): void
    {
        Storage::fake('local');
        [, , $blog] = $this->tenant();
        [, $token] = $this->frontendFor($blog);
        $this->publishArticles($blog, 2);

        BuildSnapshot::dispatchSync($blog);
        $this->travel(40)->days();
        $this->publishArticles($blog, 1);
        $this->artisan('events:prune')->assertSuccessful();

        $this->withToken($token)->getJson('/api/sync?after=0')->assertStatus(410)->assertJsonPath('snapshot_required', true);
        $this->withToken($token)->getJson('/api/sync?after=2')->assertOk()->assertJsonCount(1, 'events');

        $download = $this->withToken($token)->get('/api/sync/snapshot')->assertOk()->assertHeader('X-SBBlog-Up-To-Sequence', '2');
        $snapshot = json_decode((string) gzdecode((string) $download->streamedContent()));

        $this->assertSame([], app(SchemaValidator::class)->errors($snapshot, 'events/snapshot.schema.json'));
        $types = array_map(fn (object $event) => $event->type, $snapshot->events);
        $this->assertSame('blog.updated', $types[0]);
        $this->assertSame(2, count(array_keys($types, 'article.published')));
    }

    public function test_fully_pruned_history_still_requires_the_snapshot(): void
    {
        Storage::fake('local');
        [, , $blog] = $this->tenant();
        [, $token] = $this->frontendFor($blog);
        $this->publishArticles($blog, 2);
        BuildSnapshot::dispatchSync($blog);
        $this->travel(40)->days();
        $this->artisan('events:prune')->assertSuccessful();
        $this->assertSame(0, $blog->outboxEvents()->count());

        $this->withToken($token)->getJson('/api/sync?after=0')->assertStatus(410)->assertJsonPath('oldest_sequence', 3);
        $this->withToken($token)->getJson('/api/sync?after=2')->assertOk()->assertJsonCount(0, 'events');
    }

    public function test_bad_input_is_rejected_cleanly(): void
    {
        Storage::fake('local');
        [, , $blog] = $this->tenant();
        [, $token] = $this->frontendFor($blog);

        $this->withToken($token)->getJson('/api/sync?after=-1')->assertUnprocessable();
        $this->withToken($token)->getJson('/api/sync?limit=9999')->assertUnprocessable();
        $this->withToken($token)->withHeader('X-SBBlog-Core', str_repeat('9', 200))->getJson('/api/sync')->assertOk();
        $this->assertSame(32, mb_strlen((string) Frontend::query()->sole()->core_version));

        Snapshot::factory()->for($blog)->create(['storage_path' => 'snapshots/missing.json.gz']);
        $this->withToken($token)->getJson('/api/sync/snapshot')->assertNotFound();
    }

    public function test_events_still_owed_to_a_transport_are_kept(): void
    {
        Storage::fake('local');
        [, , $blog] = $this->tenant();
        $this->publishArticles($blog, 2);
        EventTransport::factory()->for($blog)->create(['last_dispatched_sequence' => 1]);
        BuildSnapshot::dispatchSync($blog);
        $this->travel(40)->days();

        $this->artisan('events:prune')->assertSuccessful();

        $this->assertSame([2], $blog->outboxEvents()->pluck('sequence')->all());
    }

    public function test_webhooks_receive_signed_batches_and_failures_keep_the_position(): void
    {
        [, , $blog] = $this->tenant();
        $transport = EventTransport::factory()->for($blog)->create(['config' => ['url' => 'https://hooks.test/in', 'secret' => 'shh']]);
        $this->publishArticles($blog, 2);

        Http::fake(['hooks.test/*' => Http::sequence()->push('nope', 500)->push('ok', 200)]);

        DispatchEvents::dispatchSync($transport);
        $transport->refresh();
        $this->assertSame(0, $transport->last_dispatched_sequence);
        $this->assertStringContainsString('500', (string) $transport->last_error);

        DispatchEvents::dispatchSync($transport);
        $transport->refresh();
        $this->assertSame(2, $transport->last_dispatched_sequence);
        $this->assertNull($transport->last_error);

        Http::assertSent(function (ClientRequest $request) {
            $signature = 'sha256='.hash_hmac('sha256', $request->body(), 'shh');

            return $request->hasHeader('X-SBBlog-Signature', $signature) && count($request['events']) === 2;
        });
    }

    public function test_console_creates_frontends_with_a_one_time_token(): void
    {
        [$owner, $workspace, $blog] = $this->tenant();
        $base = "/{$workspace->slug}/{$blog->slug}/publishing";

        $this->actingAs($owner)->post("{$base}/frontends", ['name' => 'Site', 'base_url' => 'https://blog.test', 'store_adapter' => 'sqlite'])
            ->assertSessionHas('new_sync_token');
        $token = session('new_sync_token')['token'];

        $this->assertSame(SyncToken::hash($token), Frontend::query()->sole()->sync_token_hash);
        $this->withToken($token)->getJson('/api/sync')->assertOk();

        $frontend = Frontend::query()->sole();
        $this->actingAs($owner)->post("{$base}/frontends/{$frontend->id}/token");
        $this->withToken($token)->getJson('/api/sync')->assertUnauthorized();
    }

    public function test_publishing_needs_frontends_manage(): void
    {
        [, $workspace, $blog] = $this->tenant();
        $editor = $this->member($workspace, WorkspaceRole::Member, $blog, 'editor');

        $this->actingAs($editor)->get("/{$workspace->slug}/{$blog->slug}/publishing")->assertForbidden();
        $this->actingAs($editor)->get("/{$workspace->slug}/{$blog->slug}/publishing/events")->assertForbidden();
    }

    public function test_deleted_workspaces_stop_all_background_work(): void
    {
        [$owner, $workspace, $blog] = $this->tenant();
        [, $token] = $this->frontendFor($blog);
        $this->publishArticles($blog, 1);
        EventTransport::factory()->for($blog)->create(['last_dispatched_sequence' => 0]);
        $scheduled = Article::factory()->for($blog)->create();
        app(ArticleEditor::class)->schedule($scheduled, now()->addMinute()->toImmutable(), $owner);

        $workspace->delete();
        $this->travel(5)->minutes();

        $this->withToken($token)->getJson('/api/sync')->assertUnauthorized();
        $this->artisan('events:dispatch')->expectsOutput('Queued 0 transport(s).');
        $this->artisan('snapshots:build')->expectsOutput('Queued 0 snapshot(s).');
        $this->artisan('articles:publish-scheduled')->expectsOutput('Published 0 article(s).');
    }

    public function test_dispatch_command_queues_only_transports_behind(): void
    {
        [, , $blog] = $this->tenant();
        $this->publishArticles($blog, 1);
        EventTransport::factory()->for($blog)->create(['last_dispatched_sequence' => 1]);
        $behind = EventTransport::factory()->for($blog)->create(['last_dispatched_sequence' => 0, 'config' => ['url' => 'https://hooks.test/x', 'secret' => 's']]);
        Http::fake();

        $this->artisan('events:dispatch')->expectsOutput('Queued 1 transport(s).');

        $this->assertSame(1, $behind->refresh()->last_dispatched_sequence);
    }
}
