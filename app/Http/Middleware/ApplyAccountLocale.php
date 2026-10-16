<?php

namespace App\Http\Middleware;

use App\Support\AppLocale;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pages without a language prefix (admin, settings, auth) follow the signed-in
 * user's saved language, then the `locale` cookie, then the default. Must run after
 * the session starts and before `HandleInertiaRequests` shares the locale.
 */
class ApplyAccountLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! AppLocale::isLocalizable($request->getPathInfo())) {
            $preferred = $request->user()?->locale ?? $request->cookie(AppLocale::COOKIE);

            app()->setLocale(AppLocale::isAvailable($preferred) ? $preferred : AppLocale::DEFAULT);
        }

        return $next($request);
    }
}
