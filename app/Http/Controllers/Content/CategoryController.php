<?php

namespace App\Http\Controllers\Content;

use App\Actions\Content\Taxonomy;
use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Category;
use App\Models\Workspace;
use App\Support\Slug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(Workspace $workspace, Blog $blog): Response
    {
        return Inertia::render('content/categories', [
            'categories' => $blog->categories()
                ->withCount('articles')
                ->orderBy('position')
                ->orderBy('name')
                ->get(['id', 'parent_id', 'name', 'slug', 'bgcolor', 'position']),
            'trashed' => $blog->categories()->onlyTrashed()->orderByDesc('deleted_at')->get(['id', 'name', 'slug', 'deleted_at']),
        ]);
    }

    public function store(Request $request, Workspace $workspace, Blog $blog, Taxonomy $taxonomy): RedirectResponse
    {
        $taxonomy->saveCategory($blog, $this->validated($request, $blog));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category created.')]);

        return back();
    }

    public function update(Request $request, Workspace $workspace, Blog $blog, Category $category, Taxonomy $taxonomy): RedirectResponse
    {
        $taxonomy->saveCategory($blog, $this->validated($request, $blog, $category), $category);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category updated.')]);

        return back();
    }

    public function move(Request $request, Workspace $workspace, Blog $blog, Category $category, Taxonomy $taxonomy): RedirectResponse
    {
        $request->validate(['direction' => ['required', Rule::in(['up', 'down'])]]);

        $taxonomy->moveCategory($blog, $category, $request->string('direction')->toString());

        return back();
    }

    public function destroy(Workspace $workspace, Blog $blog, Category $category, Taxonomy $taxonomy): RedirectResponse
    {
        $taxonomy->deleteCategory($blog, $category);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category deleted.')]);

        return back();
    }

    public function restore(Workspace $workspace, Blog $blog, Category $category, Taxonomy $taxonomy): RedirectResponse
    {
        $taxonomy->restoreCategory($blog, $category);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category restored.')]);

        return back();
    }

    /**
     * @return array{name: string, slug: string, parent_id: string|null, bgcolor: string|null}
     */
    private function validated(Request $request, Blog $blog, ?Category $category = null): array
    {
        $request->merge(['slug' => Str::lower(trim((string) $request->input('slug'))) ?: Str::slug((string) $request->input('name'))]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:120', 'regex:'.Slug::PATTERN, Rule::unique('categories', 'slug')->where('blog_id', $blog->id)->ignore($category?->id)],
            'parent_id' => ['nullable', 'uuid', Rule::exists('categories', 'id')->where('blog_id', $blog->id)->whereNull('deleted_at'), Rule::notIn(array_filter([$category?->id]))],
            'bgcolor' => ['nullable', 'hex_color'],
        ]);

        return [
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'parent_id' => $validated['parent_id'] ?? null,
            'bgcolor' => $validated['bgcolor'] ?? null,
        ];
    }
}
