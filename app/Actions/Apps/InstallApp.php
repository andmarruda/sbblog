<?php

namespace App\Actions\Apps;

use App\Models\App;
use App\Models\AppInstallation;
use App\Models\Blog;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * The authorization-code dance: the user approves an app for a blog, the app trades the
 * one-time code (10 minutes) for a token tied to that installation.
 */
class InstallApp
{
    public const CODE_TTL_MINUTES = 10;

    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  list<string>  $scopes
     */
    public function approve(App $app, Blog $blog, User $user, array $scopes, string $redirectUri): string
    {
        $installation = AppInstallation::query()->firstOrNew(['app_id' => $app->id, 'blog_id' => $blog->id]);
        $installation->fill(['granted_scopes' => $scopes, 'installed_by' => $user->id, 'revoked_at' => null])->save();

        $code = Str::random(64);
        Cache::put(self::cacheKey($code), [
            'installation' => $installation->id,
            'client_id' => $app->client_id,
            'redirect_uri' => $redirectUri,
        ], now()->addMinutes(self::CODE_TTL_MINUTES));

        $this->audit->record('app.installed', $installation, ['app' => $app->name, 'scopes' => $scopes], blog: $blog, actor: $user);

        return $code;
    }

    /**
     * Trade a code for a bearer token. Codes work once.
     *
     * @return array{access_token: string, token_type: string, scope: string, blog_id: string}|null
     */
    public function exchange(string $code, App $app, string $redirectUri): ?array
    {
        /** @var array{installation: string, client_id: string, redirect_uri: string}|null $grant */
        $grant = Cache::pull(self::cacheKey($code));

        if ($grant === null || $grant['client_id'] !== $app->client_id || $grant['redirect_uri'] !== $redirectUri) {
            return null;
        }

        $installation = AppInstallation::query()->whereKey($grant['installation'])->whereNull('revoked_at')->first();

        if ($installation === null) {
            return null;
        }

        // One live token per installation.
        $installation->tokens()->delete();
        $scopes = $installation->granted_scopes;

        return [
            'access_token' => $installation->createToken($app->name, $scopes)->plainTextToken,
            'token_type' => 'Bearer',
            'scope' => implode(' ', $scopes),
            'blog_id' => $installation->blog_id,
        ];
    }

    public function revoke(AppInstallation $installation, User $user): void
    {
        $installation->update(['revoked_at' => now()]);
        $installation->tokens()->delete();

        $this->audit->record('app.revoked', $installation, blog: $installation->blog, actor: $user);
    }

    private static function cacheKey(string $code): string
    {
        return 'oauth-code:'.hash('sha256', $code);
    }
}
