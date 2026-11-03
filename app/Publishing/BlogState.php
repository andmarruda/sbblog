<?php

namespace App\Publishing;

use App\Enums\ArticleStatus;
use App\Enums\CommentStatus;
use App\Enums\IntegrationScope;
use App\Models\Article;
use App\Models\AuthorProfile;
use App\Models\Blog;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Integration;
use App\Models\Tag;
use App\Publishing\Events\PublishableEvent;

/**
 * Everything a reader can see on a blog, as the events that would rebuild it. Used for snapshots
 * and after bulk imports.
 */
class BlogState
{
    /**
     * @return list<PublishableEvent>
     */
    public static function events(Blog $blog): array
    {
        $events = [new Events\BlogUpdated($blog)];

        $theme = $blog->activeTheme()->with('theme')->first();
        if ($theme !== null) {
            $events[] = new Events\ThemeUpdated($blog->id, $theme);
        }

        Category::query()->whereBelongsTo($blog)->orderBy('position')->each(function (Category $category) use (&$events) {
            $events[] = new Events\CategoryUpserted($category);
        });
        Tag::query()->whereBelongsTo($blog)->each(function (Tag $tag) use (&$events) {
            $events[] = new Events\TagUpserted($tag);
        });
        AuthorProfile::query()->whereBelongsTo($blog)->each(function (AuthorProfile $author) use (&$events) {
            $events[] = new Events\AuthorUpserted($author);
        });

        Article::query()
            ->whereBelongsTo($blog)
            ->where('status', ArticleStatus::Published)
            ->whereNotNull('published_revision_id')
            ->with(['publishedRevision', 'language', 'authorProfile', 'category', 'tags'])
            ->each(function (Article $article) use (&$events) {
                $events[] = new Events\ArticlePublished($article);
            });

        Integration::query()
            ->whereBelongsTo($blog)
            ->where('enabled', true)
            ->orderBy('created_at')
            ->get()
            ->groupBy(fn (Integration $integration) => $integration->scope->value.'|'.($integration->author_profile_id ?? $integration->article_id ?? ''))
            ->each(function ($group) use ($blog, &$events) {
                /** @var Integration $first */
                $first = $group->first();
                $target = $first->scope === IntegrationScope::Blog ? null : ($first->author_profile_id ?? $first->article_id);
                $events[] = new Events\IntegrationsUpdated($blog->id, $first->scope, $target, $group->values());
            });

        Comment::query()
            ->whereBelongsTo($blog)
            ->where('status', CommentStatus::Approved)
            ->where('is_public', true)
            ->whereHas('article', fn ($query) => $query->where('status', ArticleStatus::Published))
            ->orderBy('created_at')
            ->each(function (Comment $comment) use (&$events) {
                $events[] = new Events\CommentApproved($comment);
            });

        return $events;
    }
}
