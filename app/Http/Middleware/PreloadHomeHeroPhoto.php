<?php

namespace App\Http\Middleware;

use App\Support\HeroPhoto;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Announces the homepage's LCP photo in an HTTP `Link` header, so the browser
 * requests it before it has downloaded and parsed the HTML (Vite's own preload
 * headers only cover scripts and styles).
 */
class PreloadHomeHeroPhoto
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->routeIs('home') && ! $request->header('X-Inertia')) {
            $response->headers->set('Link', HeroPhoto::linkHeader(), false);
        }

        return $response;
    }
}
