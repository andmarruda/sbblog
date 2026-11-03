<?php

namespace App\Http\Controllers\Apps;

use App\Apps\Scopes;
use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\Workspace;
use App\Support\AuditLogger;
use App\Support\Slug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Apps a workspace builds on top of the API.
 */
class AppController extends Controller
{
    public function index(Request $request, Workspace $workspace): Response
    {
        return Inertia::render('workspace-settings/apps', [
            'apps' => $workspace->apps()->withCount(['installations' => fn ($query) => $query->whereNull('revoked_at')])->orderBy('name')->get()
                ->map(fn (App $app) => [
                    'id' => $app->id,
                    'name' => $app->name,
                    'client_id' => $app->client_id,
                    'redirect_uris' => $app->redirect_uris,
                    'requested_scopes' => $app->requested_scopes,
                    'installations_count' => $app->installations_count,
                ]),
            'scopes' => Scopes::ALL,
            'authorizeUrl' => route('oauth.authorize'),
            'tokenUrl' => route('oauth.token'),
            'newSecret' => $request->session()->get('new_client_secret'),
        ]);
    }

    public function store(Request $request, Workspace $workspace, AuditLogger $audit): RedirectResponse
    {
        $validated = $this->validated($request);
        $secret = Str::random(48);

        $app = $workspace->apps()->create([
            ...$validated,
            'slug' => Slug::unique($validated['name'], fn (string $slug) => App::query()->where('slug', $slug)->exists()),
            'is_first_party' => false,
            'client_id' => 'app_'.Str::lower(Str::random(32)),
            'client_secret_hash' => Hash::make($secret),
        ]);

        $audit->record('app.created', $app, workspace: $workspace);

        return back()->with('new_client_secret', ['app' => $app->id, 'secret' => $secret]);
    }

    public function update(Request $request, Workspace $workspace, App $app): RedirectResponse
    {
        abort_unless($app->workspace_id === $workspace->id, 404);

        $app->update($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Saved.')]);

        return back();
    }

    public function rotateSecret(Workspace $workspace, App $app, AuditLogger $audit): RedirectResponse
    {
        abort_unless($app->workspace_id === $workspace->id, 404);

        $secret = Str::random(48);
        $app->update(['client_secret_hash' => Hash::make($secret)]);
        $audit->record('app.secret_rotated', $app, workspace: $workspace);

        return back()->with('new_client_secret', ['app' => $app->id, 'secret' => $secret]);
    }

    public function destroy(Workspace $workspace, App $app, AuditLogger $audit): RedirectResponse
    {
        abort_unless($app->workspace_id === $workspace->id, 404);

        // Deleting the app removes its installations, and with them their tokens.
        $app->installations()->each(fn ($installation) => $installation->tokens()->delete());
        $app->delete();
        $audit->record('app.deleted', null, ['name' => $app->name], workspace: $workspace);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('App deleted.')]);

        return back();
    }

    /**
     * @return array{name: string, redirect_uris: list<string>, requested_scopes: list<string>}
     */
    private function validated(Request $request): array
    {
        $request->merge(['redirect_uris' => array_values(array_filter(array_map('trim', preg_split('/\s+/', (string) $request->input('redirect_uris_text')) ?: [])))]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'redirect_uris' => ['required', 'array', 'min:1', 'max:10'],
            'redirect_uris.*' => ['url:https,http', 'max:500'],
            'requested_scopes' => ['required', 'array', 'min:1'],
            'requested_scopes.*' => [Rule::in(Scopes::keys())],
        ]);

        return [
            'name' => $validated['name'],
            'redirect_uris' => array_values($validated['redirect_uris']),
            'requested_scopes' => array_values(array_unique($validated['requested_scopes'])),
        ];
    }
}
