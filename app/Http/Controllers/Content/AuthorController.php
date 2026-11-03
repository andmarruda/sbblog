<?php

namespace App\Http\Controllers\Content;

use App\Actions\Content\AuthorProfiles;
use App\Http\Controllers\Controller;
use App\Models\AuthorProfile;
use App\Models\Blog;
use App\Models\Media;
use App\Models\SocialLink;
use App\Models\SocialNetwork;
use App\Models\User;
use App\Models\Workspace;
use App\Support\Slug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AuthorController extends Controller
{
    public function index(Workspace $workspace, Blog $blog): Response
    {
        return Inertia::render('content/authors', [
            'authors' => $blog->authorProfiles()
                ->with(['user:id,name,email', 'avatar', 'socialLinks'])
                ->withCount('articles')
                ->orderBy('display_name')
                ->get()
                ->map(fn (AuthorProfile $author) => [
                    'id' => $author->id,
                    'display_name' => $author->display_name,
                    'slug' => $author->slug,
                    'bio' => $author->bio,
                    'user' => $author->user?->only(['id', 'name', 'email']),
                    'avatar' => $author->avatar === null ? null : ['id' => $author->avatar->id, 'url' => $author->avatar->url('thumb')],
                    'articles_count' => $author->articles_count,
                    'social_links' => $author->socialLinks->mapWithKeys(fn (SocialLink $link) => [$link->social_network_id => $link->url]),
                ]),
            'members' => $workspace->members()->orderBy('name')->get(['users.id', 'users.name', 'users.email'])
                ->map(fn (User $user) => $user->only(['id', 'name', 'email'])),
            'networks' => SocialNetwork::query()->orderBy('name')->get(['id', 'key', 'name']),
        ]);
    }

    public function store(Request $request, Workspace $workspace, Blog $blog, AuthorProfiles $authors): RedirectResponse
    {
        [$data, $links] = $this->validated($request, $workspace, $blog);
        $authors->save($blog, $data, socialLinks: $links);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Author created.')]);

        return back();
    }

    public function update(Request $request, Workspace $workspace, Blog $blog, AuthorProfile $authorProfile, AuthorProfiles $authors): RedirectResponse
    {
        [$data, $links] = $this->validated($request, $workspace, $blog, $authorProfile);
        $authors->save($blog, $data, $authorProfile, $links);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Author updated.')]);

        return back();
    }

    public function destroy(Workspace $workspace, Blog $blog, AuthorProfile $authorProfile, AuthorProfiles $authors): RedirectResponse
    {
        $authors->delete($blog, $authorProfile);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Author deleted.')]);

        return back();
    }

    /**
     * @return array{array<string, mixed>, array<string, string>}
     */
    private function validated(Request $request, Workspace $workspace, Blog $blog, ?AuthorProfile $author = null): array
    {
        $request->merge(['slug' => Str::lower(trim((string) $request->input('slug'))) ?: Str::slug((string) $request->input('display_name'))]);

        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:120', 'regex:'.Slug::PATTERN, Rule::unique('author_profiles', 'slug')->where('blog_id', $blog->id)->ignore($author?->id)],
            'bio' => ['nullable', 'string', 'max:2000'],
            'user_id' => [
                'nullable', 'uuid',
                Rule::exists('workspace_members', 'user_id')->where('workspace_id', $workspace->id),
                Rule::unique('author_profiles', 'user_id')->where('blog_id', $blog->id)->ignore($author?->id),
            ],
            'avatar_media_id' => ['nullable', 'uuid', Rule::exists(Media::class, 'id')->where('blog_id', $blog->id)],
            'social_links' => ['nullable', 'array'],
            'social_links.*' => ['nullable', 'url:https,http', 'max:255'],
        ]);

        $links = array_filter(
            (array) ($validated['social_links'] ?? []),
            fn (mixed $url, int|string $networkId) => is_string($url) && $url !== '' && SocialNetwork::query()->whereKey((string) $networkId)->exists(),
            ARRAY_FILTER_USE_BOTH,
        );

        unset($validated['social_links']);

        return [$validated, $links];
    }
}
