<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\Blog;
use App\Models\User;

class ArticlePolicy
{
    public function view(User $user, Article $article): bool
    {
        return $user->can('articles.view', $this->blog($article));
    }

    /**
     * Editors change any article; authors only the ones they created or sign.
     */
    public function update(User $user, Article $article): bool
    {
        $blog = $this->blog($article);

        return $user->can('articles.edit_any', $blog)
            || ($user->can('articles.edit_own', $blog) && $this->owns($user, $article));
    }

    public function publish(User $user, Article $article): bool
    {
        return $user->can('articles.publish', $this->blog($article));
    }

    public function delete(User $user, Article $article): bool
    {
        return $user->can('articles.delete', $this->blog($article));
    }

    public function restore(User $user, Article $article): bool
    {
        return $this->delete($user, $article);
    }

    public function suggest(User $user, Article $article): bool
    {
        return $user->can('suggestions.create', $this->blog($article));
    }

    public function review(User $user, Article $article): bool
    {
        return $user->can('suggestions.review', $this->blog($article));
    }

    public function owns(User $user, Article $article): bool
    {
        return $article->created_by === $user->id
            || $article->authorProfile()->where('user_id', $user->id)->exists();
    }

    private function blog(Article $article): Blog
    {
        /** @var Blog */
        return $article->relationLoaded('blog') ? $article->blog : $article->blog()->withTrashed()->firstOrFail();
    }
}
