<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public const COOKIE = 'locale';

    /**
     * Pick the interface language: the user's preference, then the guest cookie,
     * then the browser's Accept-Language, then the app default.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        App::setLocale(self::resolve($request));

        return $next($request);
    }

    public static function resolve(Request $request): string
    {
        /** @var list<string> $supported */
        $supported = config('app.supported_locales');

        $candidates = [
            $request->user()?->language?->code,
            $request->cookie(self::COOKIE),
            self::fromBrowser($request, $supported),
            config('app.locale'),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && in_array($candidate, $supported, true)) {
                return $candidate;
            }
        }

        return $supported[0];
    }

    /**
     * @param  list<string>  $supported
     */
    private static function fromBrowser(Request $request, array $supported): ?string
    {
        // Symfony speaks "pt_BR"; our codes are "pt-BR".
        $preferred = $request->getPreferredLanguage(
            array_map(fn (string $locale) => str_replace('-', '_', $locale), $supported),
        );

        return $preferred === null ? null : str_replace('_', '-', $preferred);
    }
}
