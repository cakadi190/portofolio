<?php

namespace App\Http\Middleware;

use App\Support\AppLocale;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Global middleware (runs before routing): serves `/en/blog` from the `/blog`
 * route by stripping the language prefix and switching the app locale. The default
 * language never carries a prefix, so `/id/blog` is redirected to `/blog`.
 */
class ResolveLocalePrefix
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = '/'.ltrim($request->getPathInfo(), '/');
        $segment = explode('/', trim($path, '/'))[0];

        if ($segment === AppLocale::DEFAULT) {
            $stripped = $this->strip($request);

            if (AppLocale::isLocalizable($this->pathOf($stripped))) {
                return redirect($stripped, 301);
            }
        }

        if (in_array($segment, AppLocale::prefixed(), true)) {
            $stripped = $this->strip($request);

            if (AppLocale::isLocalizable($this->pathOf($stripped))) {
                $this->rewrite($request, $stripped);
                app()->setLocale($segment);
                $request->attributes->set(AppLocale::URL_LOCALE_ATTRIBUTE, $segment);

                return $next($request);
            }
        }

        app()->setLocale(AppLocale::DEFAULT);

        return $next($request);
    }

    /**
     * The request URI without its leading language segment.
     */
    private function strip(Request $request): string
    {
        $rest = substr($request->getRequestUri(), strlen('/'.explode('/', trim($request->getPathInfo(), '/'))[0]));

        return $rest === '' || $rest[0] === '?' ? "/{$rest}" : $rest;
    }

    private function pathOf(string $uri): string
    {
        return parse_url($uri, PHP_URL_PATH) ?: '/';
    }

    private function rewrite(Request $request, string $uri): void
    {
        $server = $request->server->all();
        $server['REQUEST_URI'] = $uri;

        $request->initialize(
            $request->query->all(),
            $request->request->all(),
            $request->attributes->all(),
            $request->cookies->all(),
            $request->files->all(),
            $server,
            $request->getContent(),
        );
    }
}
