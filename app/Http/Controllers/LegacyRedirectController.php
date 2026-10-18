<?php

namespace App\Http\Controllers;

use App\Support\AppLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Wayfinder\Attributes\WayfinderIgnore;

/**
 * Permanently redirects a legacy Indonesian URL to its English route, keeping the
 * query string (Laravel's own `Route::permanentRedirect` drops it, which would lose
 * list filters such as `?region=Ngawi&search=kopi` on old bookmarks and links).
 *
 * The destination comes from the route's `destination` default and may reuse the
 * route's own parameters, e.g. `/services/{slug}`.
 *
 * Left out of the generated TypeScript: no page links to these old URLs, and `Route::any`
 * makes Wayfinder emit a `query` method its runtime types do not know yet.
 */
#[WayfinderIgnore]
class LegacyRedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $route = $request->route();

        $destination = preg_replace_callback(
            '/\{(\w+)\}/',
            fn (array $match): string => rawurlencode((string) $route->parameter($match[1])),
            (string) $route->defaults['destination'],
        );

        $query = $request->getQueryString();

        $destination = AppLocale::localize($destination, AppLocale::DEFAULT);

        return redirect()->to($query === null ? $destination : "{$destination}?{$query}", 301);
    }
}
