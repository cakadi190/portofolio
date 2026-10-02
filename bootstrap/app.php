<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\MinifyHtmlResponse;
use App\Http\Middleware\VerifyTurnstile;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['sidebar_state']);

        $middleware->web(append: [
            VerifyTurnstile::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            MinifyHtmlResponse::class,
        ]);

        // Nginx terminates TLS and proxies to the app container over plain
        // HTTP (see deploy/nginx/cakadi.web.id.conf), so without this
        // Laravel never sees the request as secure and generates
        // http:// asset/URL links on an https:// page — mixed content
        // blocked by the browser. TRUSTED_PROXIES is set to "*" in
        // docker-compose.prod.yml because only nginx can reach the
        // container (127.0.0.1-only port publish).
        $middleware->trustProxies(
            at: env('TRUSTED_PROXIES', ''),
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
