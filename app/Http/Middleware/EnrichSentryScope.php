<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Sentry\State\Scope;
use Sentry\UserDataBag;
use Symfony\Component\HttpFoundation\Response;

use function Sentry\configureScope;

/**
 * Attach privacy-safe debugging context to the Sentry scope: correlation id,
 * route, actor role and a pseudonymous user reference. Never the user's
 * name, email or IP address.
 */
class EnrichSentryScope
{
    /**
     * Stable, non-reversible reference for a user (HMAC with the app key).
     */
    public static function userReference(User $user): string
    {
        return substr(hash_hmac('sha256', 'user:'.$user->getKey(), (string) config('app.key')), 0, 16);
    }

    public static function actor(?User $user): string
    {
        return match (true) {
            $user === null => 'guest',
            $user->isAdmin() => 'admin',
            $user->canManageBlog() => 'redaktur',
            default => 'user',
        };
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $actor = self::actor($user);

        configureScope(function (Scope $scope) use ($request, $user, $actor): void {
            $scope->setTag('request_id', (string) $request->attributes->get(AssignRequestId::ATTRIBUTE));
            $scope->setTag('actor', $actor);
            $scope->setTag('inertia', $request->header('X-Inertia') ? 'true' : 'false');
            $scope->setTag('area', $request->is('admin*') ? 'admin' : ($request->is('settings*', 'dashboard*') ? 'account' : 'public'));
            $scope->setContext('request_meta', ['method' => $request->method(), 'path' => $request->path()]);
            if ($user === null) {
                $scope->removeUser();
            } else {
                $scope->setUser(UserDataBag::createFromUserIdentifier(self::userReference($user))->setSegment($actor));
            }
        });

        $response = $next($request);

        configureScope(function (Scope $scope) use ($request, $response): void {
            $scope->setTag('route', (string) ($request->route()?->getName() ?? 'unnamed'));
            $scope->setTag('http_status', (string) $response->getStatusCode());
        });

        return $response;
    }
}
