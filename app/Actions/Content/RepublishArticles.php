<?php

namespace App\Actions\Content;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Blog;
use App\Publishing\EventRecorder;
use App\Publishing\Events\ArticlePublished;
use Illuminate\Database\Eloquent\Builder;

/**
 * Re-emits article.published for live articles whose snapshot changed without a new revision,
 * e.g. a merged tag or a deleted category. Must run inside the caller's transaction.
 */
class RepublishArticles
{
    public function __construct(private readonly EventRecorder $events) {}

    /**
     * @param  Builder<Article>  $articles
     */
    public function handle(Blog $blog, Builder $articles): int
    {
        $count = 0;

        $articles->clone()
            ->whereBelongsTo($blog)
            ->where('status', ArticleStatus::Published)
            ->whereNotNull('published_revision_id')
            ->with(['publishedRevision', 'language', 'authorProfile', 'category', 'tags'])
            ->each(function (Article $article) use ($blog, &$count) {
                $this->events->record($blog, new ArticlePublished($article));
                $count++;
            });

        return $count;
    }
}
