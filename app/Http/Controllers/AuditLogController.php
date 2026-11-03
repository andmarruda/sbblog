<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Blog;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function workspace(Request $request, Workspace $workspace): Response
    {
        return $this->render($request, AuditLog::query()->where('workspace_id', $workspace->id), 'workspace-settings/audit');
    }

    public function blog(Request $request, Workspace $workspace, Blog $blog): Response
    {
        return $this->render($request, AuditLog::query()->where('blog_id', $blog->id), 'blog-settings/audit');
    }

    /**
     * @param  Builder<AuditLog>  $query
     */
    private function render(Request $request, Builder $query, string $component): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'actor' => ['nullable', 'uuid'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $logs = $query
            ->with(['actorUser:id,name,email', 'actorAppInstallation.app:id,name', 'blog:id,name'])
            ->when($filters['search'] ?? null, fn (Builder $q, string $search) => $q->where('action', 'ilike', '%'.addcslashes($search, '%_\\').'%'))
            ->when($filters['actor'] ?? null, fn (Builder $q, string $actor) => $q->where('actor_user_id', $actor))
            ->when($filters['from'] ?? null, fn (Builder $q, string $from) => $q->where('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $q, string $to) => $q->where('created_at', '<', now()->parse($to)->addDay()))
            ->latest('created_at')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (AuditLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'actor' => $log->actorUser?->only(['id', 'name', 'email'])
                    ?? ($log->actorAppInstallation?->app ? ['name' => $log->actorAppInstallation->app->name, 'app' => true] : null),
                'blog' => $log->blog?->name,
                'subject_type' => $log->subject_type,
                'subject_id' => $log->subject_id,
                'metadata' => $log->metadata,
                'created_at' => $log->created_at?->toIso8601String(),
            ]);

        return Inertia::render($component, [
            'logs' => $logs,
            'query' => $request->only(['search', 'actor', 'from', 'to']),
        ]);
    }
}
