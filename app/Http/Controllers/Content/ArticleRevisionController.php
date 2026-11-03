<?php

namespace App\Http\Controllers\Content;

use App\Actions\Content\ArticleEditor;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleRevision;
use App\Models\Blog;
use App\Models\User;
use App\Models\Workspace;
use App\Support\RevisionDiff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ArticleRevisionController extends Controller
{
    public function index(Request $request, Workspace $workspace, Blog $blog, Article $article): Response
    {
        Gate::authorize('view', $article);

        $revisions = $article->revisions()
            ->with(['createdByUser:id,name', 'createdByAppInstallation.app:id,name'])
            ->reorder('number', 'desc')
            ->get();

        $validated = $request->validate([
            'from' => ['nullable', 'uuid', Rule::in($revisions->pluck('id'))],
            'to' => ['nullable', 'uuid', Rule::in($revisions->pluck('id'))],
        ]);

        $to = $revisions->firstWhere('id', $validated['to'] ?? $article->current_revision_id) ?? $revisions->first();
        $from = $revisions->firstWhere('id', $validated['from'] ?? null)
            ?? $revisions->first(fn (ArticleRevision $revision) => $to !== null && $revision->number < $to->number);

        return Inertia::render('content/articles/revisions', [
            'article' => ['id' => $article->id, 'title' => $article->currentRevision->title ?? ''],
            'revisions' => $revisions->map(fn (ArticleRevision $revision) => [
                'id' => $revision->id,
                'number' => $revision->number,
                'title' => $revision->title,
                'author' => $revision->createdByUser->name ?? $revision->createdByAppInstallation?->app?->name,
                'created_at' => $revision->created_at?->toIso8601String(),
                'is_current' => $revision->id === $article->current_revision_id,
                'is_published' => $revision->id === $article->published_revision_id,
            ]),
            'from' => $from?->id,
            'to' => $to?->id,
            'diff' => $to === null ? null : RevisionDiff::between($from, $to),
            'canRestore' => $request->user()?->can('update', $article) ?? false,
        ]);
    }

    public function restore(Request $request, Workspace $workspace, Blog $blog, Article $article, ArticleRevision $revision, ArticleEditor $editor): RedirectResponse
    {
        Gate::authorize('update', $article);

        /** @var User $user */
        $user = $request->user();
        $editor->restoreRevision($article, $revision, $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Revision restored as the latest draft.')]);

        return to_route('articles.edit', [$workspace->slug, $blog->slug, $article->id]);
    }
}
