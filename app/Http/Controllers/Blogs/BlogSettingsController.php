<?php

namespace App\Http\Controllers\Blogs;

use App\Enums\CommentPolicy;
use App\Http\Controllers\Controller;
use App\Http\Requests\Blogs\UpdateBlogRequest;
use App\Models\Blog;
use App\Models\Language;
use App\Models\Workspace;
use App\Publishing\EventRecorder;
use App\Publishing\Events\BlogUpdated;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BlogSettingsController extends Controller
{
    public function edit(Workspace $workspace, Blog $blog): Response
    {
        return Inertia::render('blog-settings/general', [
            'blog' => [
                ...$blog->only([
                    'id', 'name', 'slug', 'title', 'slogan', 'description', 'canonical_url',
                    'default_language_id', 'autoconvert_webp',
                ]),
                'comment_policy' => $blog->comment_policy->value,
                'language_ids' => $blog->languages()->pluck('languages.id'),
                'brand_media_id' => $blog->brand_media_id,
                'brand' => $blog->brandMedia === null ? null : ['url' => $blog->brandMedia->url('thumb')],
            ],
            'languages' => Language::query()->orderBy('label')->get(['id', 'code', 'label']),
            'commentPolicies' => array_map(fn (CommentPolicy $policy) => $policy->value, CommentPolicy::cases()),
        ]);
    }

    public function update(UpdateBlogRequest $request, Workspace $workspace, Blog $blog, AuditLogger $audit, EventRecorder $events): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($blog, $validated, $audit, $events) {
            $blog->fill(collect($validated)->except('language_ids')->all());
            $changed = array_keys($blog->getDirty());
            $blog->save();

            $languages = $blog->languages()->sync($validated['language_ids']);

            if ($languages['attached'] !== [] || $languages['detached'] !== []) {
                $changed[] = 'languages';
            }

            if ($changed !== []) {
                $audit->record('blog.updated', $blog, ['fields' => $changed], blog: $blog);
                $events->record($blog, new BlogUpdated($blog->unsetRelation('languages')));
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Blog updated.')]);

        return to_route('blogs.settings.general', [$workspace->slug, $blog->slug]);
    }

    public function destroy(Request $request, Workspace $workspace, Blog $blog, AuditLogger $audit): RedirectResponse
    {
        $request->validate([
            'confirmation' => ['required', Rule::in([$blog->slug])],
        ]);

        $audit->record('blog.deleted', $blog, blog: $blog);
        $blog->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Blog deleted. It can be restored from the workspace settings.')]);

        return to_route('workspaces.settings.blogs', $workspace->slug);
    }
}
