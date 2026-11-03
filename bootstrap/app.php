<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // TLS ends at the shared nginx in front of the container (the container port is never
        // published), so trust its X-Forwarded-* headers for scheme, host and client IP.
        $middleware->trustProxies(at: '*');

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state', SetLocale::COOKIE]);

        $middleware->append(SecurityHeaders::class);

        $middleware->web(append: [
            SetLocale::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            // Only the first assets: a header with every chunk outgrows proxy buffers (~7 KB).
            AddLinkHeadersForPreloadedAssets::using(10),
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Console errors render as Inertia pages. Server errors keep the debug page while debugging.
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            $status = $response->getStatusCode();

            if ($request->is('api/*') || $request->expectsJson()) {
                return $response;
            }

            if ($status === 419 || $status === 429) {
                Inertia::flash('toast', ['type' => 'error', 'message' => $status === 419
                    ? __('The page expired, please try again.')
                    : __('Too many attempts. Wait a moment and try again.')]);

                return back();
            }

            $debugging = config('app.debug') && $status >= 500;

            if (in_array($status, [403, 404, 500, 503], true) && ! $debugging) {
                // Unmatched routes 404 before the web middleware runs, so apply locale and shared props here.
                App::setLocale(SetLocale::resolve($request));
                Inertia::share(app(HandleInertiaRequests::class)->share($request));

                // Authorization denials can carry a reason worth showing (e.g. 2FA required).
                $reason = $status === 403 && $e->getPrevious() instanceof AuthorizationException ? $e->getMessage() : null;

                return Inertia::render('errors/error', ['status' => $status, 'reason' => $reason !== 'This action is unauthorized.' ? $reason : null])
                    ->toResponse($request)
                    ->setStatusCode($status);
            }

            return $response;
        });
    })->create();
