<?php

namespace App\Http\Controllers\Content;

use App\Actions\Content\Taxonomy;
use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Tag;
use App\Models\Workspace;
use App\Support\Slug;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TagController extends Controller
{
    public function index(Request $request, Workspace $workspace, Blog $blog): Response
    {
        $search = $request->string('search')->trim()->toString();
        $sort = in_array($request->query('sort'), ['name', 'articles_count'], true) ? (string) $request->query('sort') : 'name';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';

        return Inertia::render('content/tags', [
            'tags' => $blog->tags()
                ->withCount('articles')
                ->when($search !== '', fn ($query) => $query->where('name', 'ilike', '%'.addcslashes($search, '%_\\').'%'))
                ->orderBy($sort, $direction)
                ->paginate(25)
                ->withQueryString(),
            'query' => $request->only(['search', 'sort', 'direction']),
        ]);
    }

    public function store(Request $request, Workspace $workspace, Blog $blog, Taxonomy $taxonomy): JsonResponse|RedirectResponse
    {
        $tag = $taxonomy->saveTag($blog, $this->validated($request, $blog));

        if ($request->expectsJson()) {
            return response()->json($tag->only(['id', 'name', 'slug']), 201);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tag created.')]);

        return back();
    }

    public function update(Request $request, Workspace $workspace, Blog $blog, Tag $tag, Taxonomy $taxonomy): RedirectResponse
    {
        $taxonomy->saveTag($blog, $this->validated($request, $blog, $tag), $tag);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tag updated.')]);

        return back();
    }

    public function destroy(Workspace $workspace, Blog $blog, Tag $tag, Taxonomy $taxonomy): RedirectResponse
    {
        $taxonomy->deleteTag($blog, $tag);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tag deleted.')]);

        return back();
    }

    public function merge(Request $request, Workspace $workspace, Blog $blog, Tag $tag, Taxonomy $taxonomy): RedirectResponse
    {
        $validated = $request->validate([
            'target_id' => ['required', 'uuid', Rule::exists('tags', 'id')->where('blog_id', $blog->id)],
        ]);

        $taxonomy->mergeTags($blog, $tag, $blog->tags()->whereKey($validated['target_id'])->firstOrFail());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tags merged.')]);

        return back();
    }

    /**
     * @return array{name: string, slug: string}
     */
    private function validated(Request $request, Blog $blog, ?Tag $tag = null): array
    {
        $request->merge(['slug' => Str::lower(trim((string) $request->input('slug'))) ?: Str::slug((string) $request->input('name'))]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'slug' => ['required', 'string', 'max:80', 'regex:'.Slug::PATTERN, Rule::unique('tags', 'slug')->where('blog_id', $blog->id)->ignore($tag?->id)],
        ]);

        return ['name' => $validated['name'], 'slug' => $validated['slug']];
    }
}
