<?php

namespace App\Http\Controllers\Apps;

use App\Actions\Apps\InstallApp;
use App\Apps\Scopes;
use App\Authorization\PermissionResolver;
use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\Blog;
use App\Models\User;
use App\Support\TenantDirectory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * OAuth 2 authorization code flow, minus refresh tokens: apps get a token bound to one blog.
 */
class OAuthController extends Controller
{
    public function show(Request $request, PermissionResolver $permissions): Response
    {
        [$app, $redirectUri, $scopes] = $this->client($request);

        /** @var User $user */
        $user = $request->user();

        $blogs = collect(TenantDirectory::for($user))
            ->flatMap(fn (array $workspace) => collect($workspace['blogs'])->map(fn (array $blog) => [...$blog, 'workspace' => $workspace['name']]))
            ->filter(fn (array $blog) => Gate::forUser($user)->allows('apps.install', Blog::query()->whereKey($blog['id'])->firstOrFail()))
            ->values();

        return Inertia::render('oauth/authorize', [
            'app' => ['name' => $app->name, 'first_party' => $app->is_first_party],
            'scopes' => array_map(fn (string $scope) => ['key' => $scope, 'description' => Scopes::ALL[$scope]], $scopes),
            'blogs' => $blogs,
            'request' => $request->only(['client_id', 'redirect_uri', 'scope', 'state']),
            'redirectHost' => parse_url($redirectUri, PHP_URL_HOST),
        ]);
    }

    public function approve(Request $request, InstallApp $install): HttpResponse
    {
        [$app, $redirectUri, $scopes] = $this->client($request);

        /** @var User $user */
        $user = $request->user();
        $query = array_filter(['state' => $request->string('state')->toString()]);

        if (! $request->boolean('approve')) {
            return Inertia::location($this->redirect($redirectUri, ['error' => 'access_denied', ...$query]));
        }

        $validated = $request->validate(['blog_id' => ['required', 'uuid']]);
        $blog = Blog::query()->active()->whereKey($validated['blog_id'])->firstOrFail();
        Gate::forUser($user)->authorize('apps.install', $blog);

        $code = $install->approve($app, $blog, $user, $scopes, $redirectUri);

        return Inertia::location($this->redirect($redirectUri, ['code' => $code, ...$query]));
    }

    public function token(Request $request, InstallApp $install): JsonResponse
    {
        $validated = $request->validate([
            'grant_type' => ['required', 'in:authorization_code'],
            'code' => ['required', 'string'],
            'client_id' => ['required', 'string'],
            'client_secret' => ['required', 'string'],
            'redirect_uri' => ['required', 'string'],
        ]);

        $app = App::query()->where('client_id', $validated['client_id'])->first();

        if ($app === null || ! Hash::check($validated['client_secret'], $app->client_secret_hash)) {
            return response()->json(['error' => 'invalid_client'], 401);
        }

        $token = $install->exchange($validated['code'], $app, $validated['redirect_uri']);

        return $token === null
            ? response()->json(['error' => 'invalid_grant'], 400)
            : response()->json($token);
    }

    /**
     * The app, an exact registered redirect URI and the scopes asked for (all of them must be
     * among the scopes the app registered).
     *
     * @return array{App, string, list<string>}
     */
    private function client(Request $request): array
    {
        $request->validate([
            'client_id' => ['required', 'string'],
            'redirect_uri' => ['required', 'string'],
            'scope' => ['nullable', 'string'],
            'state' => ['nullable', 'string', 'max:255'],
        ]);

        $app = App::query()->where('client_id', $request->string('client_id')->toString())->first();
        $redirectUri = $request->string('redirect_uri')->toString();

        // Never redirect to a URI the app didn't register: that would leak codes.
        abort_if($app === null || ! in_array($redirectUri, $app->redirect_uris, true), 400, __('Unknown app or redirect address.'));

        $asked = array_values(array_filter(explode(' ', $request->string('scope')->toString())));
        $scopes = $asked === [] ? $app->requested_scopes : array_values(array_intersect($asked, $app->requested_scopes));

        abort_if($scopes === [] || count($scopes) !== count(array_unique($asked === [] ? $scopes : $asked)), 400, __('The app asked for scopes it did not register.'));

        return [$app, $redirectUri, $scopes];
    }

    /**
     * @param  array<string, string>  $query
     */
    private function redirect(string $uri, array $query): string
    {
        return $uri.(str_contains($uri, '?') ? '&' : '?').http_build_query($query);
    }
}
