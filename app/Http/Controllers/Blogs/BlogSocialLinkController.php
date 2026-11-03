<?php

namespace App\Http\Controllers\Blogs;

use App\Actions\Content\AuthorProfiles;
use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\SocialLink;
use App\Models\SocialNetwork;
use App\Models\Workspace;
use App\Publishing\EventRecorder;
use App\Publishing\Events\BlogUpdated;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BlogSocialLinkController extends Controller
{
    public function edit(Workspace $workspace, Blog $blog): Response
    {
        return Inertia::render('blog-settings/social', [
            'networks' => SocialNetwork::query()->orderBy('name')->get(['id', 'key', 'name']),
            'links' => $blog->socialLinks()->get()->mapWithKeys(fn (SocialLink $link) => [$link->social_network_id => $link->url]),
        ]);
    }

    public function update(Request $request, Workspace $workspace, Blog $blog, AuthorProfiles $authors, EventRecorder $events, AuditLogger $audit): RedirectResponse
    {
        $validated = $request->validate([
            'links' => ['nullable', 'array'],
            'links.*' => ['nullable', 'url:https,http', 'max:255'],
        ]);

        $links = array_filter(
            (array) ($validated['links'] ?? []),
            fn (mixed $url, int|string $networkId) => is_string($url) && $url !== '' && SocialNetwork::query()->whereKey((string) $networkId)->exists(),
            ARRAY_FILTER_USE_BOTH,
        );

        DB::transaction(function () use ($blog, $links, $authors, $events, $audit) {
            $authors->syncBlogLinks($blog, $links);
            $events->record($blog, new BlogUpdated($blog->unsetRelation('socialLinks')));
            $audit->record('blog.social_links_updated', $blog, blog: $blog);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Social links saved.')]);

        return back();
    }
}
