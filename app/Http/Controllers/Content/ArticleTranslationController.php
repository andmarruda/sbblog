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
use Illuminate\Support\Facades\Gate;

class ArticleTranslationController extends Controller
{
    public function store(Request $request, Workspace $workspace, Blog $blog, Article $article, ArticleEditor $editor): RedirectResponse
    {
        Gate::authorize('view', $article);
        Gate::authorize('articles.create', $blog);

        $validated = $request->validate(['language_id' => ['required', 'uuid']]);

        /** @var User $user */
        $user = $request->user();
        $translation = $editor->translate($article, $validated['language_id'], $user);

        return to_route('articles.edit', [$workspace->slug, $blog->slug, $translation->id]);
    }
}
