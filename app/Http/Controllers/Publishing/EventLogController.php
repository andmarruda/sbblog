<?php

namespace App\Http\Controllers\Publishing;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\OutboxEvent;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class EventLogController extends Controller
{
    public function __invoke(Request $request, Workspace $workspace, Blog $blog): Response
    {
        $filters = $request->validate([
            'type' => ['nullable', 'string', 'max:64'],
            'search' => ['nullable', 'string', 'max:64'],
        ]);

        return Inertia::render('publishing/events', [
            'events' => OutboxEvent::query()
                ->whereBelongsTo($blog)
                ->when($filters['type'] ?? null, fn ($query, string $type) => $query->where('type', $type))
                ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where('aggregate_id', $search))
                ->orderByDesc('sequence')
                ->paginate(30)
                ->withQueryString()
                ->through(fn (OutboxEvent $event) => [
                    'id' => $event->id,
                    'sequence' => $event->sequence,
                    'type' => $event->type,
                    'aggregate_type' => $event->aggregate_type,
                    'aggregate_id' => $event->aggregate_id,
                    'occurred_at' => $event->occurred_at?->toIso8601String(),
                ]),
            'types' => DB::table('outbox_events')->where('blog_id', $blog->id)->distinct()->orderBy('type')->pluck('type'),
            'query' => $request->only(['type', 'search', 'event']),
            'selected' => $request->filled('event')
                ? DB::table('outbox_events')->where('blog_id', $blog->id)->where('id', (int) $request->query('event'))->selectRaw('jsonb_pretty(payload) as pretty')->value('pretty')
                : null,
        ]);
    }
}
