<?php

namespace App\Http\Controllers\Blogs;

use App\Enums\ArticleStatus;
use App\Enums\CommentStatus;
use App\Enums\IntegrationProvider;
use App\Enums\SuggestionStatus;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleSuggestion;
use App\Models\Blog;
use App\Models\Frontend;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BlogDashboardController extends Controller
{
    /**
     * What needs attention: drafts, reviews, suggestions, comments, the schedule and lagging
     * frontends. Traffic lives in the blog's analytics tool.
     */
    public function __invoke(Request $request, Workspace $workspace, Blog $blog): Response
    {
        /** @var User $user */
        $user = $request->user();

        $counts = $blog->articles()->toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        $ga4 = $blog->integrations()->where('provider', IntegrationProvider::Ga4)->where('enabled', true)->exists();

        return Inertia::render('blogs/dashboard', [
            'stats' => [
                'drafts' => (int) ($counts[ArticleStatus::Draft->value] ?? 0),
                'in_review' => (int) ($counts[ArticleStatus::InReview->value] ?? 0),
                'scheduled' => (int) ($counts[ArticleStatus::Scheduled->value] ?? 0),
                'published' => (int) ($counts[ArticleStatus::Published->value] ?? 0),
                'pending_comments' => $blog->comments()->where('status', CommentStatus::Pending)->count(),
                'pending_suggestions' => ArticleSuggestion::query()->whereHas('article', fn ($q) => $q->whereBelongsTo($blog))->where('status', SuggestionStatus::Pending)->count(),
            ],
            'myDrafts' => $this->articles(Article::query()->whereBelongsTo($blog)->where('status', ArticleStatus::Draft)
                ->where(fn ($q) => $q->where('created_by', $user->id)->orWhereHas('authorProfile', fn ($a) => $a->where('user_id', $user->id)))
                ->latest('updated_at')),
            'inReview' => $this->articles(Article::query()->whereBelongsTo($blog)->where('status', ArticleStatus::InReview)->latest('updated_at')),
            'upcoming' => $this->articles(Article::query()->whereBelongsTo($blog)->where('status', ArticleStatus::Scheduled)->orderBy('scheduled_at')),
            'laggingFrontends' => $blog->frontends()->where('last_acked_sequence', '<', $blog->event_sequence)->get()->map(fn (Frontend $frontend) => [
                'id' => $frontend->id,
                'name' => $frontend->name,
                'behind' => $blog->event_sequence - $frontend->last_acked_sequence,
            ]),
            // GA4 reports, with article/author/category dimensions, live in Google Analytics.
            'analyticsUrl' => $ga4 ? 'https://analytics.google.com/analytics/web/' : null,
        ]);
    }

    /**
     * @param  Builder<Article>  $query
     * @return array<int, array{id: string, title: string, updated_at: string|null, scheduled_at: string|null}>
     */
    private function articles(Builder $query): array
    {
        return $query->with('currentRevision:id,title')->limit(5)->get()->map(fn (Article $article) => [
            'id' => $article->id,
            'title' => $article->currentRevision->title ?? '',
            'updated_at' => $article->updated_at?->toIso8601String(),
            'scheduled_at' => $article->scheduled_at?->toIso8601String(),
        ])->values()->all();
    }
}
