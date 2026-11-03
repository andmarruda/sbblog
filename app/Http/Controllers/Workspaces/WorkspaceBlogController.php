<?php

namespace App\Http\Controllers\Workspaces;

use App\Actions\Blogs\CreateBlog;
use App\Http\Controllers\Controller;
use App\Http\Requests\Blogs\StoreBlogRequest;
use App\Models\Blog;
use App\Models\Language;
use App\Models\User;
use App\Models\Workspace;
use App\Support\AuditLogger;
use App\Support\TenantDirectory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceBlogController extends Controller
{
    public function index(Request $request, Workspace $workspace): Response
    {
        /** @var User $user */
        $user = $request->user();

        $visible = collect(TenantDirectory::for($user))->firstWhere('id', $workspace->id)['blogs'] ?? [];
        $canDelete = Gate::allows('blogs.delete', $workspace);

        return Inertia::render('workspace-settings/blogs', [
            'blogs' => Blog::query()
                ->whereIn('id', array_column($visible, 'id'))
                ->with('defaultLanguage:id,code,label')
                ->withCount('articles')
                ->orderBy('name')
                ->get(['id', 'name', 'slug', 'default_language_id', 'created_at']),
            'trashed' => $canDelete
                ? $workspace->blogs()->onlyTrashed()->orderByDesc('deleted_at')->get(['id', 'name', 'slug', 'deleted_at'])
                : [],
            'languages' => Language::query()->orderBy('label')->get(['id', 'code', 'label']),
            'defaultLanguageId' => Language::query()->where('code', App::getLocale())->value('id'),
        ]);
    }

    public function store(StoreBlogRequest $request, Workspace $workspace, CreateBlog $createBlog): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();

        $blog = $createBlog->handle($workspace, $user, $validated['name'], $validated['language_id'], $validated['slug'] ?? null);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Blog created.')]);

        return to_route('blogs.dashboard', [$workspace->slug, $blog->slug]);
    }

    public function restore(Workspace $workspace, string $blogId, AuditLogger $audit): RedirectResponse
    {
        $blog = $workspace->blogs()->onlyTrashed()->findOrFail($blogId);
        $blog->restore();

        $audit->record('blog.restored', $blog, blog: $blog);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Blog restored.')]);

        return back();
    }
}
