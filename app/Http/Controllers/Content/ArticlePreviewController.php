<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Blog;
use App\Models\Workspace;
use App\Publishing\Payloads\ArticlePayload;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ArticlePreviewController extends Controller
{
    /**
     * The latest draft rendered exactly as frontends will receive it.
     */
    public function __invoke(Workspace $workspace, Blog $blog, Article $article): Response
    {
        Gate::authorize('view', $article);

        $payload = ArticlePayload::make($article, $article->currentRevision);

        return Inertia::render('content/articles/preview', [
            'article' => [
                'id' => $article->id,
                'title' => $payload['title'],
                'description' => $payload['description'],
                'html' => $payload['html'],
                'cover' => $payload['cover'],
                'author' => $payload['author']['display_name'] ?? null,
                'category' => $payload['category']['name'] ?? null,
                'tags' => array_column($payload['tags'], 'name'),
            ],
        ]);
    }
}
