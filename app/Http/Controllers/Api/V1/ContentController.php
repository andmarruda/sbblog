<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Content\ArticleEditor;
use App\Actions\Content\StoreMedia;
use App\Actions\Content\Suggestions;
use App\Apps\ApiAccess;
use App\Enums\ArticleStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Content\SaveDraftRequest;
use App\Models\AppInstallation;
use App\Models\Article;
use App\Models\AuthorProfile;
use App\Models\Blog;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Content API v1 (docs/v2/api/openapi.yaml). Writes never publish: they create draft revisions
 * and suggestions that people review.
 */
class ContentController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        $actor = ApiAccess::actor($request);

        return response()->json($actor instanceof AppInstallation
            ? ['type' => 'app', 'app' => $actor->app?->name, 'blog_id' => $actor->blog_id, 'scopes' => $actor->granted_scopes]
            : ['type' => 'user', 'name' => $actor->name, 'email' => $actor->email]);
    }

    public function articles(Request $request, Blog $blog): JsonResponse
    {
        ApiAccess::authorize($request, $blog, 'articles:read', 'articles.view');

        $validated = $request->validate([
            'status' => ['nullable', Rule::enum(ArticleStatus::class)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return response()->json($blog->articles()
            ->with(['currentRevision:id,title', 'language:id,code'])
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->latest('updated_at')
            ->paginate((int) ($validated['per_page'] ?? 25))
            ->through(fn (Article $article) => $this->summary($article)));
    }

    public function article(Request $request, Blog $blog, Article $article): JsonResponse
    {
        ApiAccess::authorize($request, $blog, 'articles:read', 'articles.view');

        $revision = $article->currentRevision;

        return response()->json([
            ...$this->summary($article),
            'category_id' => $article->category_id,
            'author_profile_id' => $article->author_profile_id,
            'tag_ids' => $article->tags()->pluck('tags.id'),
            'revision' => [
                'id' => $revision?->id,
                'number' => $revision?->number,
                'title' => $revision?->title,
                'description' => $revision?->description,
                'body' => $revision?->body,
                'cover_media_id' => $revision?->cover_media_id,
            ],
        ]);
    }

    /**
     * Save a new draft revision. People need edit rights; apps need articles:write.
     */
    public function saveDraft(SaveDraftRequest $request, Blog $blog, Article $article, ArticleEditor $editor): JsonResponse
    {
        $actor = ApiAccess::authorize($request, $blog, 'articles:write');

        if ($actor instanceof User) {
            abort_unless(Gate::forUser($actor)->allows('update', $article), 403);
        }

        $revision = $editor->saveDraft($article, $actor, $request->content());

        return response()->json(['revision_id' => $revision->id, 'number' => $revision->number], 201);
    }

    public function suggest(SaveDraftRequest $request, Blog $blog, Article $article, Suggestions $suggestions): JsonResponse
    {
        $actor = ApiAccess::authorize($request, $blog, 'articles:write', 'suggestions.create');

        $suggestion = $suggestions->create($article, $actor, $request->content(), $request->string('note')->toString() ?: null);

        return response()->json(['suggestion_id' => $suggestion->id, 'status' => $suggestion->status->value], 201);
    }

    public function upload(Request $request, Blog $blog, StoreMedia $store): JsonResponse
    {
        $actor = ApiAccess::authorize($request, $blog, 'media:write', 'media.upload');

        $request->validate([
            'file' => ['required', 'file', 'max:'.config('sbblog.media.max_kilobytes'), 'mimes:'.implode(',', config('sbblog.media.mimes'))],
            'alt_text' => ['nullable', 'string', 'max:500'],
        ]);

        /** @var UploadedFile $file */
        $file = $request->file('file');
        $media = $store->handle($blog, $actor, $file, $request->string('alt_text')->toString() ?: null);

        return response()->json(['id' => $media->id, 'url' => $media->url(), 'width' => $media->width, 'height' => $media->height], 201);
    }

    public function taxonomy(Request $request, Blog $blog): JsonResponse
    {
        ApiAccess::authorize($request, $blog, 'taxonomy:read', 'articles.view');

        return response()->json([
            'categories' => $blog->categories()->orderBy('position')->get()->map(fn (Category $category) => $category->only(['id', 'name', 'slug', 'parent_id'])),
            'tags' => $blog->tags()->orderBy('name')->get()->map(fn (Tag $tag) => $tag->only(['id', 'name', 'slug'])),
            'authors' => $blog->authorProfiles()->orderBy('display_name')->get()->map(fn (AuthorProfile $author) => $author->only(['id', 'display_name', 'slug'])),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(Article $article): array
    {
        return [
            'id' => $article->id,
            'title' => $article->currentRevision->title ?? '',
            'slug' => $article->slug,
            'status' => $article->status->value,
            'language' => $article->language?->code,
            'translation_group_id' => $article->translation_group_id,
            'published_at' => $article->published_at?->toIso8601String(),
            'updated_at' => $article->updated_at?->toIso8601String(),
        ];
    }
}
