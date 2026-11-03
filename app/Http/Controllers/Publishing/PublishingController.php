<?php

namespace App\Http\Controllers\Publishing;

use App\Enums\StoreAdapter;
use App\Enums\TransportDriver;
use App\Http\Controllers\Controller;
use App\Jobs\BuildSnapshot;
use App\Jobs\DispatchEvents;
use App\Models\Blog;
use App\Models\EventTransport;
use App\Models\Frontend;
use App\Models\Snapshot;
use App\Models\Workspace;
use App\Support\AuditLogger;
use App\Support\SafeUrl;
use App\Support\SyncToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * Frontends, snapshots and push transports of one blog.
 */
class PublishingController extends Controller
{
    public function index(Request $request, Workspace $workspace, Blog $blog): Response
    {
        return Inertia::render('publishing/index', [
            'latestSequence' => $blog->event_sequence,
            'frontends' => $blog->frontends()->orderBy('name')->get()->map(fn (Frontend $frontend) => [
                'id' => $frontend->id,
                'name' => $frontend->name,
                'base_url' => $frontend->base_url,
                'store_adapter' => $frontend->store_adapter->value,
                'last_acked_sequence' => $frontend->last_acked_sequence,
                'behind' => max(0, $blog->event_sequence - $frontend->last_acked_sequence),
                'last_sync_at' => $frontend->last_sync_at?->toIso8601String(),
                'core_version' => $frontend->core_version,
            ]),
            'snapshots' => $blog->snapshots()->latest('up_to_sequence')->limit(5)->get()->map(fn (Snapshot $snapshot) => [
                'id' => $snapshot->id,
                'up_to_sequence' => $snapshot->up_to_sequence,
                'size_bytes' => $snapshot->size_bytes,
                'created_at' => $snapshot->created_at?->toIso8601String(),
            ]),
            'transports' => $blog->eventTransports()->orderBy('created_at')->get()->map(fn (EventTransport $eventTransport) => [
                'id' => $eventTransport->id,
                'driver' => $eventTransport->driver->value,
                'url' => $eventTransport->config['url'] ?? null,
                'enabled' => $eventTransport->enabled,
                'last_dispatched_sequence' => $eventTransport->last_dispatched_sequence,
                'behind' => max(0, $blog->event_sequence - $eventTransport->last_dispatched_sequence),
                'last_error' => $eventTransport->last_error,
                'last_error_at' => $eventTransport->last_error_at?->toIso8601String(),
            ]),
            'storeAdapters' => array_map(fn (StoreAdapter $adapter) => $adapter->value, StoreAdapter::cases()),
            'syncUrl' => route('sync.events'),
            // Shown once, right after creating or rotating a token.
            'newToken' => $request->session()->get('new_sync_token'),
        ]);
    }

    public function storeFrontend(Request $request, Workspace $workspace, Blog $blog, AuditLogger $audit): RedirectResponse
    {
        $validated = $this->validateFrontend($request);
        $token = SyncToken::generate();

        $frontend = $blog->frontends()->create([...$validated, 'sync_token_hash' => SyncToken::hash($token)]);
        $audit->record('frontend.created', $frontend, ['name' => $frontend->name], blog: $blog);

        return back()->with('new_sync_token', ['frontend' => $frontend->id, 'token' => $token]);
    }

    public function updateFrontend(Request $request, Workspace $workspace, Blog $blog, Frontend $frontend): RedirectResponse
    {
        $frontend->update($this->validateFrontend($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Saved.')]);

        return back();
    }

    public function rotateToken(Workspace $workspace, Blog $blog, Frontend $frontend, AuditLogger $audit): RedirectResponse
    {
        $token = SyncToken::generate();
        $frontend->update(['sync_token_hash' => SyncToken::hash($token)]);
        $audit->record('frontend.token_rotated', $frontend, blog: $blog);

        return back()->with('new_sync_token', ['frontend' => $frontend->id, 'token' => $token]);
    }

    public function destroyFrontend(Workspace $workspace, Blog $blog, Frontend $frontend, AuditLogger $audit): RedirectResponse
    {
        $frontend->delete();
        $audit->record('frontend.deleted', null, ['name' => $frontend->name], blog: $blog);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Frontend removed. Its token no longer works.')]);

        return back();
    }

    public function buildSnapshot(Workspace $workspace, Blog $blog): RedirectResponse
    {
        BuildSnapshot::dispatch($blog);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Snapshot requested. It appears here when ready.')]);

        return back();
    }

    public function storeTransport(Request $request, Workspace $workspace, Blog $blog, AuditLogger $audit): RedirectResponse
    {
        $eventTransport = $blog->eventTransports()->create([
            'driver' => TransportDriver::Webhook,
            'config' => [...$this->validateWebhook($request), 'secret' => Str::random(40)],
            'enabled' => true,
            // Start from now: history is available through a snapshot.
            'last_dispatched_sequence' => $blog->event_sequence,
        ]);

        $audit->record('transport.created', $eventTransport, ['driver' => 'webhook'], blog: $blog);

        return back()->with('new_sync_token', ['transport' => $eventTransport->id, 'token' => $eventTransport->config['secret'] ?? '']);
    }

    public function updateTransport(Request $request, Workspace $workspace, Blog $blog, EventTransport $eventTransport): RedirectResponse
    {
        $validated = $request->validate(['enabled' => ['required', 'boolean']]);

        $eventTransport->update([
            'enabled' => $validated['enabled'],
            'config' => $request->has('url') ? [...($eventTransport->config ?? []), ...$this->validateWebhook($request)] : $eventTransport->config,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Saved.')]);

        return back();
    }

    public function testTransport(Workspace $workspace, Blog $blog, EventTransport $eventTransport): RedirectResponse
    {
        $ping = [[
            'sequence' => 0,
            'type' => 'transport.test',
            'schema_version' => 1,
            'aggregate_type' => 'blog',
            'aggregate_id' => $blog->id,
            'occurred_at' => now()->toIso8601String(),
            'payload' => (object) [],
        ]];

        try {
            DispatchEvents::driver($eventTransport->driver)->send($eventTransport, $ping);
        } catch (Throwable $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);

            return back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Test event delivered.')]);

        return back();
    }

    public function destroyTransport(Workspace $workspace, Blog $blog, EventTransport $eventTransport, AuditLogger $audit): RedirectResponse
    {
        $eventTransport->delete();
        $audit->record('transport.deleted', null, ['driver' => $eventTransport->driver->value], blog: $blog);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transport removed.')]);

        return back();
    }

    /**
     * @return array{name: string, base_url: string, store_adapter: string}
     */
    private function validateFrontend(Request $request): array
    {
        /** @var array{name: string, base_url: string, store_adapter: string} */
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'base_url' => ['required', 'url:https,http', 'max:255'],
            'store_adapter' => ['required', Rule::enum(StoreAdapter::class)],
        ]);
    }

    /**
     * @return array{url: string}
     */
    private function validateWebhook(Request $request): array
    {
        // HTTPS only outside local development.
        $schemes = app()->isProduction() ? 'https' : 'https,http';

        /** @var array{url: string} */
        return $request->validate(['url' => [
            'required', "url:{$schemes}", 'max:500',
            fn (string $attribute, mixed $value, \Closure $fail) => SafeUrl::isPublic((string) $value)
                ? null
                : $fail(__('This address is not reachable from the internet.')),
        ]]);
    }
}
