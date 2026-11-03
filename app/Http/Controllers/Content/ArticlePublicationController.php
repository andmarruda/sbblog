<?php

namespace App\Http\Controllers\Content;

use App\Actions\Content\ArticleEditor;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Blog;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ArticlePublicationController extends Controller
{
    public function publish(Workspace $workspace, Blog $blog, Article $article, ArticleEditor $editor, Request $request): RedirectResponse
    {
        Gate::authorize('publish', $article);

        $editor->publish($article, $this->user($request));

        return $this->done(__('Published.'));
    }

    public function schedule(Request $request, Workspace $workspace, Blog $blog, Article $article, ArticleEditor $editor): RedirectResponse
    {
        Gate::authorize('publish', $article);

        $validated = $request->validate(['scheduled_at' => ['required', 'date', 'after:now']]);

        $editor->schedule($article, Carbon::parse($validated['scheduled_at'])->utc()->toImmutable(), $this->user($request));

        return $this->done(__('Scheduled.'));
    }

    public function unpublish(Request $request, Workspace $workspace, Blog $blog, Article $article, ArticleEditor $editor): RedirectResponse
    {
        Gate::authorize('publish', $article);

        $editor->unpublish($article, $this->user($request));

        return $this->done(__('Moved back to drafts.'));
    }

    public function archive(Request $request, Workspace $workspace, Blog $blog, Article $article, ArticleEditor $editor): RedirectResponse
    {
        Gate::authorize('publish', $article);

        $editor->archive($article, $this->user($request));

        return $this->done(__('Archived.'));
    }

    public function submitForReview(Request $request, Workspace $workspace, Blog $blog, Article $article, ArticleEditor $editor): RedirectResponse
    {
        Gate::authorize('update', $article);

        $editor->submitForReview($article, $this->user($request));

        return $this->done(__('Sent for review.'));
    }

    private function user(Request $request): User
    {
        /** @var User */
        return $request->user();
    }

    private function done(string $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }
}
