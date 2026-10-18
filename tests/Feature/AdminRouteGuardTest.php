<?php

use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\Route;

/**
 * Safeguard: a new admin route fails the build until it sits behind a gate.
 * Register it in routes/admin.php under `can:manage-blog` (admins + redaktur,
 * blogging area only) or `can:manage-site` (admins only), then — only for the
 * blogging area — add its name prefix to RedakturAccessTest's `$bloggingRoutes`.
 */
function adminRoutes(): array
{
    return collect(Route::getRoutes()->getRoutes())
        ->filter(fn (LaravelRoute $route) => $route->uri() === 'admin' || str_starts_with($route->uri(), 'admin/'))
        ->all();
}

test('every route under /admin requires a signed in, verified user and exactly one role gate', function () {
    $routes = adminRoutes();

    expect($routes)->not->toBeEmpty();

    foreach ($routes as $route) {
        $middleware = $route->gatherMiddleware();
        $gates = array_values(array_intersect($middleware, ['can:manage-blog', 'can:manage-site']));
        $label = implode('|', $route->methods()).' '.$route->uri();

        expect(in_array('auth', $middleware, true) && in_array('verified', $middleware, true))->toBeTrue("{$label} is missing auth/verified");

        // The dashboard (`/admin`) is open to any account and redirects plain users itself.
        if ($route->getName() !== 'dashboard') {
            expect($gates)->toHaveCount(1, "{$label} must carry exactly one of can:manage-blog / can:manage-site");
        }
    }
});

test('no admin-only route is reachable through the blogging gate', function () {
    $siteOnly = ['admin.users.', 'admin.system-settings.', 'admin.activity-logs.', 'admin.visitor-analytics.', 'admin.contact-messages.', 'admin.portfolios.', 'admin.media.index', 'admin.media.update', 'admin.media.destroy'];

    foreach (adminRoutes() as $route) {
        if (str($route->getName())->startsWith($siteOnly)) {
            expect($route->gatherMiddleware())->toContain('can:manage-site')->not->toContain('can:manage-blog');
        }
    }
});

test('user management mutations demand a fresh password confirmation', function () {
    foreach (['admin.users.store', 'admin.users.update', 'admin.users.destroy'] as $name) {
        expect(Route::getRoutes()->getByName($name)->gatherMiddleware())->toContain('password.confirm:password.confirm,900');
    }
});
