<?php

namespace App\Http\Controllers\Blogs;

use App\Enums\ArticleStatus;
use App\Enums\CommentStatus;
use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Workspace;
use Inertia\Inertia;
use Inertia\Response;

class BlogDashboardController extends Controller
{
    public function __invoke(Workspace $workspace, Blog $blog): Response
    {
        $counts = $blog->articles()
            ->toBase()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return Inertia::render('blogs/dashboard', [
            'stats' => [
                'drafts' => (int) ($counts[ArticleStatus::Draft->value] ?? 0),
                'in_review' => (int) ($counts[ArticleStatus::InReview->value] ?? 0),
                'scheduled' => (int) ($counts[ArticleStatus::Scheduled->value] ?? 0),
                'published' => (int) ($counts[ArticleStatus::Published->value] ?? 0),
                'pending_comments' => $blog->comments()->where('status', CommentStatus::Pending)->count(),
            ],
        ]);
    }
}
