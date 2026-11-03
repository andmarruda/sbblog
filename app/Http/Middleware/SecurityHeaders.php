<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security headers for every response, including a nonce-based Content-Security-Policy: only the
 * console's own scripts (and the inline theme script, which carries the nonce) can run.
 */
class SecurityHeaders
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Vite::useCspNonce();

        $response = $next($request);

        $response->headers->set('Content-Security-Policy', $this->policy($nonce));
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function policy(string $nonce): string
    {
        // The Vite dev server serves scripts, styles and HMR from another origin.
        $dev = Vite::isRunningHot() ? rtrim((string) file_get_contents(Vite::hotFile())) : null;
        $devSocket = $dev === null ? null : preg_replace('#^http#', 'ws', $dev);

        $directives = [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", "'nonce-{$nonce}'", $dev],
            // Radix and Tiptap set inline style attributes.
            'style-src' => ["'self'", "'unsafe-inline'", $dev],
            'img-src' => ["'self'", 'data:', 'blob:', 'https:'],
            'font-src' => ["'self'", 'data:', $dev],
            'connect-src' => ["'self'", $dev, $devSocket],
            // Video embeds previewed in the editor.
            'frame-src' => ['https://www.youtube-nocookie.com', 'https://player.vimeo.com'],
            'object-src' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
            'frame-ancestors' => ["'none'"],
        ];

        return implode('; ', array_map(
            fn (string $name, array $sources) => $name.' '.implode(' ', array_filter($sources)),
            array_keys($directives),
            $directives,
        ));
    }
}
