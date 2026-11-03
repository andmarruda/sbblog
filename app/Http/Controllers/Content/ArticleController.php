<?php

namespace App\Http\Controllers\Content;

use App\Actions\Content\ArticleEditor;
use App\Enums\ArticleStatus;
use App\Enums\SuggestionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Content\SaveDraftRequest;
use App\Http\Requests\Content\UpdateArticleDetailsRequest;
use App\Models\Article;
use App\Models\ArticleSuggestion;
use App\Models\Blog;
use App\Models\Media;
use App\Models\Tag;
use App\Models\User;
use App\Models\Workspace;
use App\Publishing\Body\TiptapDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ArticleController extends Controller
{
    public function index(Request $request, Workspace $workspace, Blog $blog): Response
    {
        Gate::authorize('articles.view', $blog);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(ArticleStatus::class)],
            'author' => ['nullable', 'uuid'],
            'category' => ['nullable', 'uuid'],
            'language' => ['nullable', 'uuid'],
            'tag' => ['nullable', 'uuid'],
            'trashed' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(['title', 'updated_at', 'published_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);

        $articles = Article::query()
            ->whereBelongsTo($blog)
            ->when($filters['trashed'] ?? false, fn (Builder $query) => $query->onlyTrashed())
            ->leftJoin('article_revisions as current', 'current.id', '=', 'articles.current_revision_id')
            ->select('articles.*', 'current.title as title')
            ->with(['authorProfile:id,display_name', 'category:id,name', 'language:id,code'])
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where(fn (Builder $q) => $q
                ->where('current.title', 'ilike', '%'.addcslashes($search, '%_\\').'%')
                ->orWhere('articles.slug', 'ilike', '%'.addcslashes($search, '%_\\').'%')))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('articles.status', $status))
            ->when($filters['author'] ?? null, fn (Builder $query, string $id) => $query->where('articles.author_profile_id', $id))
            ->when($filters['category'] ?? null, fn (Builder $query, string $id) => $query->where('articles.category_id', $id))
            ->when($filters['language'] ?? null, fn (Builder $query, string $id) => $query->where('articles.language_id', $id))
            ->when($filters['tag'] ?? null, fn (Builder $query, string $id) => $query->whereHas('tags', fn (Builder $q) => $q->whereKey($id)))
            ->orderBy(match ($filters['sort'] ?? 'updated_at') {
                'title' => 'current.title',
                'published_at' => 'articles.published_at',
                default => 'articles.updated_at',
            }, $filters['direction'] ?? 'desc')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Article $article) => [
                'id' => $article->id,
                'title' => $article->getAttribute('title') ?? '',
                'slug' => $article->slug,
                'status' => $article->status->value,
                'author' => $article->authorProfile?->display_name,
                'category' => $article->category?->name,
                'language' => $article->language?->code,
                'has_unpublished_changes' => $article->status === ArticleStatus::Published && $article->current_revision_id !== $article->published_revision_id,
                'updated_at' => $article->updated_at?->toIso8601String(),
                'published_at' => $article->published_at?->toIso8601String(),
                'scheduled_at' => $article->scheduled_at?->toIso8601String(),
                'deleted_at' => $article->deleted_at?->toIso8601String(),
            ]);

        return Inertia::render('content/articles/index', [
            'articles' => $articles,
            'query' => $request->only(['search', 'status', 'author', 'category', 'language', 'tag', 'trashed', 'sort', 'direction']),
            'filters' => [
                'authors' => $blog->authorProfiles()->orderBy('display_name')->get(['id', 'display_name']),
                'categories' => $blog->categories()->orderBy('name')->get(['id', 'name']),
                'languages' => $blog->languages()->orderBy('label')->get(['languages.id', 'code', 'label']),
                'tags' => $blog->tags()->orderBy('name')->get(['id', 'name']),
                'defaultLanguageId' => $blog->default_language_id,
            ],
        ]);
    }

    public function store(Request $request, Workspace $workspace, Blog $blog, ArticleEditor $editor): RedirectResponse
    {
        Gate::authorize('articles.create', $blog);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'language_id' => ['nullable', 'uuid', Rule::exists('blog_languages', 'language_id')->where('blog_id', $blog->id)],
        ]);

        /** @var User $user */
        $user = $request->user();
        $article = $editor->create($blog, $user, $validated['title'], $validated['language_id'] ?? null);

        return to_route('articles.edit', [$workspace->slug, $blog->slug, $article->id]);
    }

    public function edit(Request $request, Workspace $workspace, Blog $blog, Article $article): Response
    {
        Gate::authorize('view', $article);

        /** @var User $user */
        $user = $request->user();
        $article->load(['currentRevision.coverMedia', 'tags:id,name', 'language:id,code,label', 'authorProfile:id,display_name']);
        $revision = $article->currentRevision;
        $body = $revision->body ?? TiptapDocument::empty();

        $media = Media::query()
            ->whereBelongsTo($blog)
            ->whereIn('id', array_filter([...TiptapDocument::mediaIds($body), $revision?->cover_media_id]))
            ->get()
            ->mapWithKeys(fn (Media $item) => [$item->id => ['url' => $item->url(), 'alt' => $item->alt_text]]);

        return Inertia::render('content/articles/edit', [
            'article' => [
                'id' => $article->id,
                'slug' => $article->slug,
                'status' => $article->status->value,
                'category_id' => $article->category_id,
                'author_profile_id' => $article->author_profile_id,
                'bgcolor' => $article->bgcolor,
                'tag_ids' => $article->tags->pluck('id'),
                'language' => $article->language?->only(['id', 'code', 'label']),
                'translation_group_id' => $article->translation_group_id,
                'scheduled_at' => $article->scheduled_at?->toIso8601String(),
                'published_at' => $article->published_at?->toIso8601String(),
                'is_live' => $article->status === ArticleStatus::Published && $article->published_revision_id !== null,
                'has_unpublished_changes' => $article->published_revision_id !== null && $article->current_revision_id !== $article->published_revision_id,
            ],
            'revision' => [
                'id' => $revision?->id,
                'number' => $revision?->number,
                'title' => $revision->title ?? '',
                'description' => $revision?->description,
                'body' => $body,
                'cover_media_id' => $revision?->cover_media_id,
                'created_at' => $revision?->created_at?->toIso8601String(),
            ],
            'media' => $media,
            'translations' => Article::query()
                ->where('translation_group_id', $article->translation_group_id)
                ->whereKeyNot($article->id)
                ->with('language:id,code,label')
                ->get(['id', 'language_id', 'status', 'slug'])
                ->map(fn (Article $translation) => [
                    'id' => $translation->id,
                    'status' => $translation->status->value,
                    'language' => $translation->language?->only(['id', 'code', 'label']),
                ]),
            'options' => [
                'categories' => $blog->categories()->orderBy('position')->orderBy('name')->get(['id', 'name', 'parent_id']),
                'authors' => $blog->authorProfiles()->orderBy('display_name')->get(['id', 'display_name']),
                'tags' => $blog->tags()->orderBy('name')->get(['id', 'name']),
                'languages' => $blog->languages()->orderBy('label')->get(['languages.id', 'code', 'label']),
            ],
            'pendingSuggestions' => ArticleSuggestion::query()->whereBelongsTo($article)->where('status', SuggestionStatus::Pending)->count(),
            'abilities' => [
                'update' => $user->can('update', $article),
                'publish' => $user->can('publish', $article),
                'delete' => $user->can('delete', $article),
                'suggest' => $user->can('suggest', $article),
                'review' => $user->can('review', $article),
                'manageTags' => $user->can('tags.manage', $blog),
                'uploadMedia' => $user->can('media.upload', $blog),
                'create' => $user->can('articles.create', $blog),
            ],
        ]);
    }

    /**
     * Save the editor content. Autosaves coalesce; explicit saves always keep a revision.
     */
    public function saveDraft(SaveDraftRequest $request, Workspace $workspace, Blog $blog, Article $article, ArticleEditor $editor): RedirectResponse
    {
        Gate::authorize('update', $article);

        /** @var User $user */
        $user = $request->user();
        $editor->saveDraft($article, $user, $request->content(), $request->boolean('autosave'));

        return back();
    }

    public function updateDetails(UpdateArticleDetailsRequest $request, Workspace $workspace, Blog $blog, Article $article, ArticleEditor $editor): RedirectResponse
    {
        Gate::authorize('update', $article);

        $data = $request->validated();

        if (array_key_exists('tag_ids', $data) && ! $request->user()?->can('tags.manage', $blog)) {
            // Without tag management, only existing tags can be picked — which the rules already ensure.
            $data['tag_ids'] = Tag::query()->whereBelongsTo($blog)->whereIn('id', $data['tag_ids'])->pluck('id')->all();
        }

        $editor->updateDetails($article, $data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Saved.')]);

        return back();
    }

    public function destroy(Request $request, Workspace $workspace, Blog $blog, Article $article, ArticleEditor $editor): RedirectResponse
    {
        Gate::authorize('delete', $article);

        /** @var User $user */
        $user = $request->user();
        $editor->delete($article, $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Article moved to the trash.')]);

        return to_route('articles.index', [$workspace->slug, $blog->slug]);
    }

    public function restore(Request $request, Workspace $workspace, Blog $blog, Article $article, ArticleEditor $editor): RedirectResponse
    {
        Gate::authorize('restore', $article);

        /** @var User $user */
        $user = $request->user();
        $editor->restore($article, $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Article restored.')]);

        return back();
    }

    /**
     * Archive, delete or restore several articles. Each one is checked on its own.
     */
    public function bulk(Request $request, Workspace $workspace, Blog $blog, ArticleEditor $editor): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['archive', 'delete', 'restore'])],
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['uuid'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $action = $request->string('action')->toString();
        $done = 0;

        Article::withTrashed()->whereBelongsTo($blog)->whereIn('id', $validated['ids'])->each(function (Article $article) use ($action, $user, $editor, &$done) {
            $ability = match ($action) {
                'archive' => 'publish',
                'delete' => 'delete',
                default => 'restore',
            };

            if (! $user->can($ability, $article)) {
                return;
            }

            match ($action) {
                'archive' => $article->trashed() ? null : $editor->archive($article, $user),
                'delete' => $article->trashed() ? null : $editor->delete($article, $user),
                default => $article->trashed() ? $editor->restore($article, $user) : null,
            };

            $done++;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => trans_choice(':count article updated.|:count articles updated.', $done, ['count' => $done])]);

        return back();
    }
}
