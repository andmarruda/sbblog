<?php

namespace App\Http\Middleware;

use App\Models\Frontend;
use App\Support\SyncToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sync API auth: `Authorization: Bearer sbf_…`. The frontend is available as
 * `$request->attributes->get('frontend')`.
 */
class AuthenticateFrontend
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) $request->bearerToken();

        $frontend = str_starts_with($token, SyncToken::PREFIX)
            ? Frontend::query()
                ->where('sync_token_hash', SyncToken::hash($token))
                ->whereHas('blog', fn ($query) => $query->active())
                ->with('blog')
                ->first()
            : null;

        if ($frontend === null || $frontend->blog === null) {
            return response()->json(['message' => 'Invalid sync token.'], 401);
        }

        $request->attributes->set('frontend', $frontend);

        return $next($request);
    }
}
