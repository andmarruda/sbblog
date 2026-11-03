<?php

namespace App\Http\Controllers\Content;

use App\Actions\Content\Integrations;
use App\Enums\ConsentCategory;
use App\Enums\IntegrationPlacement;
use App\Enums\IntegrationProvider;
use App\Enums\IntegrationScope;
use App\Http\Controllers\Controller;
use App\Integrations\EffectiveScripts;
use App\Integrations\Providers;
use App\Models\Blog;
use App\Models\Integration;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class IntegrationController extends Controller
{
    public function index(Request $request, Workspace $workspace, Blog $blog): Response
    {
        $preview = $request->filled('article')
            ? $blog->articles()->whereKey($request->string('article')->toString())->first()
            : null;

        return Inertia::render('content/integrations', [
            'integrations' => Integration::query()
                ->whereBelongsTo($blog)
                ->with(['authorProfile:id,display_name', 'article.currentRevision:id,title'])
                ->orderBy('scope')
                ->orderBy('created_at')
                ->get()
                ->map(fn (Integration $integration) => [
                    'id' => $integration->id,
                    'scope' => $integration->scope->value,
                    'provider' => $integration->provider->value,
                    'config' => $integration->provider === IntegrationProvider::Custom
                        ? ['name' => $integration->config['name'] ?? '', 'html' => $integration->config['html'] ?? '']
                        : $integration->config,
                    'placement' => $integration->placement->value,
                    'consent_category' => $integration->consent_category->value,
                    'page_rule' => $integration->page_rule['only'] ?? 'all',
                    'enabled' => $integration->enabled,
                    'author_profile_id' => $integration->author_profile_id,
                    'article_id' => $integration->article_id,
                    'target' => $integration->authorProfile->display_name ?? $integration->article?->currentRevision?->title,
                ]),
            'authors' => $blog->authorProfiles()->orderBy('display_name')->get(['id', 'display_name']),
            'articles' => $blog->articles()
                ->join('article_revisions', 'article_revisions.id', '=', 'articles.current_revision_id')
                ->orderByDesc('articles.updated_at')
                ->limit(200)
                ->get(['articles.id', 'article_revisions.title']),
            'preview' => [
                'article' => $preview?->id,
                'html' => EffectiveScripts::render($blog, $preview),
            ],
            'canRawScripts' => $request->user()?->can('scripts.raw', $blog) ?? false,
        ]);
    }

    public function store(Request $request, Workspace $workspace, Blog $blog, Integrations $integrations): RedirectResponse
    {
        $integrations->save($blog, $this->validated($request, $blog));

        return $this->done(__('Integration saved.'));
    }

    public function update(Request $request, Workspace $workspace, Blog $blog, Integration $integration, Integrations $integrations): RedirectResponse
    {
        $this->authorizeRaw($request, $blog, $integration->provider);

        $integrations->save($blog, $this->validated($request, $blog), $integration);

        return $this->done(__('Integration saved.'));
    }

    public function toggle(Request $request, Workspace $workspace, Blog $blog, Integration $integration, Integrations $integrations): RedirectResponse
    {
        $this->authorizeRaw($request, $blog, $integration->provider);

        $integrations->save($blog, ['enabled' => ! $integration->enabled], $integration);

        return back();
    }

    public function destroy(Request $request, Workspace $workspace, Blog $blog, Integration $integration, Integrations $integrations): RedirectResponse
    {
        $this->authorizeRaw($request, $blog, $integration->provider);

        $integrations->delete($blog, $integration);

        return $this->done(__('Integration removed.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, Blog $blog): array
    {
        $provider = IntegrationProvider::tryFrom($request->string('provider')->toString());

        $request->validate([
            'provider' => ['required', Rule::enum(IntegrationProvider::class)],
            'scope' => ['required', Rule::enum(IntegrationScope::class)],
        ]);

        /** @var IntegrationProvider $provider */
        $this->authorizeRaw($request, $blog, $provider);

        $validated = $request->validate([
            'author_profile_id' => ['nullable', 'required_if:scope,author', 'prohibited_unless:scope,author', 'uuid', Rule::exists('author_profiles', 'id')->where('blog_id', $blog->id)],
            'article_id' => ['nullable', 'required_if:scope,article', 'prohibited_unless:scope,article', 'uuid', Rule::exists('articles', 'id')->where('blog_id', $blog->id)],
            'placement' => ['required', Rule::enum(IntegrationPlacement::class)],
            'consent_category' => ['required', Rule::enum(ConsentCategory::class)],
            'page_rule' => ['required', Rule::in(['all', 'articles', 'home'])],
            'enabled' => ['boolean'],
            'config' => ['required', 'array'],
            ...Providers::rules($provider),
        ]);

        // Raw scripts are global only (also enforced by a DB check).
        if ($provider === IntegrationProvider::Custom && $request->input('scope') !== IntegrationScope::Blog->value) {
            throw ValidationException::withMessages(['scope' => __('Custom scripts can only be added for the whole blog.')]);
        }

        return [
            'scope' => $request->string('scope')->toString(),
            'provider' => $provider,
            'author_profile_id' => $validated['author_profile_id'] ?? null,
            'article_id' => $validated['article_id'] ?? null,
            'placement' => $validated['placement'],
            'consent_category' => $validated['consent_category'],
            'page_rule' => $validated['page_rule'] === 'all' ? null : ['only' => $validated['page_rule']],
            'enabled' => $request->boolean('enabled', true),
            'config' => array_intersect_key((array) $validated['config'], Providers::definition($provider)['rules']),
        ];
    }

    /**
     * Raw scripts run anything on readers' pages: they need scripts.raw (2FA) and the password.
     */
    private function authorizeRaw(Request $request, Blog $blog, IntegrationProvider $provider): void
    {
        if ($provider !== IntegrationProvider::Custom) {
            return;
        }

        Gate::authorize('scripts.raw', $blog);

        $request->validate(['password' => ['required', 'current_password']]);
    }

    private function done(string $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }
}
