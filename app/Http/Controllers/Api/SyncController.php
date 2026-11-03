<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Frontend;
use App\Models\Snapshot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The pull side of the publishing log, for frontends (docs/v2/events.md).
 */
class SyncController extends Controller
{
    public const MAX_LIMIT = 500;

    public function events(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'after' => ['nullable', 'integer', 'min:0'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_LIMIT],
        ]);

        $frontend = $this->frontend($request);
        $blog = $frontend->blog;
        $after = (int) ($validated['after'] ?? 0);
        $limit = (int) ($validated['limit'] ?? 100);

        // Events up to a snapshot may have been pruned — all of them, when nothing changed since.
        // Then the frontend must load the snapshot first, or it would silently miss content.
        $oldest = DB::table('outbox_events')->where('blog_id', $frontend->blog_id)->min('sequence');
        $firstAvailable = $oldest === null ? (int) $blog?->event_sequence + 1 : (int) $oldest;

        if ($after < $firstAvailable - 1) {
            return response()->json([
                'message' => 'Events before this point were pruned. Load the latest snapshot first.',
                'snapshot_required' => true,
                'oldest_sequence' => $firstAvailable,
            ], 410);
        }

        // Payloads are passed through as stored, so `{}` stays an object.
        $rows = DB::table('outbox_events')
            ->where('blog_id', $frontend->blog_id)
            ->where('sequence', '>', $after)
            ->orderBy('sequence')
            ->limit($limit + 1)
            ->get(['sequence', 'type', 'schema_version', 'aggregate_type', 'aggregate_id', 'occurred_at', DB::raw('payload::text as payload')]);

        $frontend->forceFill([
            'last_sync_at' => now(),
            'core_version' => mb_substr((string) $request->header('X-SBBlog-Core', $frontend->core_version), 0, 32) ?: null,
        ])->saveQuietly();

        $events = $rows->take($limit)->map(fn (object $row) => [
            'sequence' => (int) $row->sequence,
            'type' => $row->type,
            'schema_version' => (int) $row->schema_version,
            'aggregate_type' => $row->aggregate_type,
            'aggregate_id' => $row->aggregate_id,
            'occurred_at' => now()->parse($row->occurred_at)->toIso8601String(),
            'payload' => json_decode((string) $row->payload),
        ])->values();

        return response()->json([
            'blog_id' => $frontend->blog_id,
            'latest_sequence' => (int) $blog?->event_sequence,
            'has_more' => $rows->count() > $limit,
            'events' => $events,
        ]);
    }

    public function ack(Request $request): JsonResponse
    {
        $validated = $request->validate(['sequence' => ['required', 'integer', 'min:0']]);

        $frontend = $this->frontend($request);
        $sequence = min((int) $validated['sequence'], (int) $frontend->blog?->event_sequence);

        // Never move backwards.
        DB::table('frontends')
            ->where('id', $frontend->id)
            ->where('last_acked_sequence', '<', $sequence)
            ->update(['last_acked_sequence' => $sequence, 'updated_at' => now()]);

        return response()->json(['last_acked_sequence' => max($sequence, $frontend->last_acked_sequence)]);
    }

    public function snapshot(Request $request): JsonResponse|StreamedResponse
    {
        $frontend = $this->frontend($request);
        $snapshot = Snapshot::query()->where('blog_id', $frontend->blog_id)->latest('up_to_sequence')->first();

        if ($snapshot === null || ! Storage::disk('local')->exists($snapshot->storage_path)) {
            return response()->json(['message' => 'No snapshot available. Ask the blog to build one.'], 404);
        }

        return Storage::disk('local')->download($snapshot->storage_path, "snapshot-{$snapshot->up_to_sequence}.json.gz", [
            'Content-Type' => 'application/gzip',
            'X-SBBlog-Up-To-Sequence' => (string) $snapshot->up_to_sequence,
        ]);
    }

    private function frontend(Request $request): Frontend
    {
        /** @var Frontend */
        return $request->attributes->get('frontend');
    }
}
