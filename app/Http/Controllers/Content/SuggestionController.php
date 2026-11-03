<?php

namespace App\Http\Controllers\Content;

use App\Actions\Content\Suggestions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Content\SaveDraftRequest;
use App\Models\Article;
use App\Models\ArticleSuggestion;
use App\Models\Blog;
use App\Models\SuggestionComment;
use App\Models\User;
use App\Models\Workspace;
use App\Support\RevisionDiff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SuggestionController extends Controller
{
    public function index(Workspace $workspace, Blog $blog, Article $article): Response
    {
        Gate::authorize('view', $article);

        return Inertia::render('content/articles/suggestions', [
            'article' => ['id' => $article->id, 'title' => $article->currentRevision->title ?? ''],
            'suggestions' => $article->suggestions()
                ->with(['requestedByUser:id,name', 'reviewer:id,name', 'proposedRevision:id,title,number'])
                ->withCount('comments')
                ->latest()
                ->get()
                ->map(fn (ArticleSuggestion $suggestion) => $this->summary($suggestion)),
        ]);
    }

    /**
     * Suggest the editor content instead of saving it (for people who can't edit the article).
     */
    public function store(SaveDraftRequest $request, Workspace $workspace, Blog $blog, Article $article, Suggestions $suggestions): RedirectResponse
    {
        Gate::authorize('suggest', $article);

        /** @var User $user */
        $user = $request->user();
        $suggestion = $suggestions->create($article, $user, $request->content(), $request->string('note')->toString() ?: null);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Suggestion sent.')]);

        return to_route('articles.suggestions.show', [$workspace->slug, $blog->slug, $article->id, $suggestion->id]);
    }

    public function show(Request $request, Workspace $workspace, Blog $blog, Article $article, ArticleSuggestion $suggestion): Response
    {
        Gate::authorize('view', $article);

        $suggestion->load(['baseRevision', 'proposedRevision', 'requestedByUser:id,name', 'reviewer:id,name', 'comments.user:id,name']);

        return Inertia::render('content/articles/suggestion', [
            'article' => ['id' => $article->id, 'title' => $article->currentRevision->title ?? ''],
            'suggestion' => [
                ...$this->summary($suggestion),
                'is_outdated' => $suggestion->base_revision_id !== $article->current_revision_id,
            ],
            'diff' => $suggestion->proposedRevision === null ? null : RevisionDiff::between($suggestion->baseRevision, $suggestion->proposedRevision),
            'comments' => $suggestion->comments->sortBy('created_at')->values()->map(fn (SuggestionComment $comment) => [
                'id' => $comment->id,
                'author' => $comment->user?->name,
                'body' => $comment->body,
                'quote' => $comment->anchor['quote'] ?? null,
                'created_at' => $comment->created_at?->toIso8601String(),
            ]),
            'canReview' => $request->user()?->can('review', $article) ?? false,
        ]);
    }

    public function accept(Request $request, Workspace $workspace, Blog $blog, Article $article, ArticleSuggestion $suggestion, Suggestions $suggestions): RedirectResponse
    {
        Gate::authorize('review', $article);

        /** @var User $user */
        $user = $request->user();
        $suggestions->accept($suggestion, $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Suggestion accepted. Publish the article to make it live.')]);

        return to_route('articles.edit', [$workspace->slug, $blog->slug, $article->id]);
    }

    public function reject(Request $request, Workspace $workspace, Blog $blog, Article $article, ArticleSuggestion $suggestion, Suggestions $suggestions): RedirectResponse
    {
        Gate::authorize('review', $article);

        $validated = $request->validate(['note' => ['nullable', 'string', 'max:2000']]);

        /** @var User $user */
        $user = $request->user();
        $suggestions->reject($suggestion, $user, $validated['note'] ?? null);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Suggestion rejected.')]);

        return back();
    }

    public function comment(Request $request, Workspace $workspace, Blog $blog, Article $article, ArticleSuggestion $suggestion, Suggestions $suggestions): RedirectResponse
    {
        Gate::authorize('view', $article);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'quote' => ['nullable', 'string', 'max:1000'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $suggestions->comment($suggestion, $user, $validated['body'], ['quote' => $validated['quote'] ?? null]);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(ArticleSuggestion $suggestion): array
    {
        return [
            'id' => $suggestion->id,
            'status' => $suggestion->status->value,
            'source' => $suggestion->source->value,
            'title' => $suggestion->proposedRevision?->title,
            'note' => $suggestion->note,
            'requested_by' => $suggestion->requestedByUser?->name,
            'reviewed_by' => $suggestion->reviewer?->name,
            'reviewed_at' => $suggestion->reviewed_at?->toIso8601String(),
            'created_at' => $suggestion->created_at?->toIso8601String(),
            'comments_count' => $suggestion->comments_count ?? $suggestion->comments()->count(),
        ];
    }
}
