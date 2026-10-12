<?php

namespace App\Http\Middleware;

use App\Services\Visitors\VisitorTracker;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Count eligible public page views after the response is sent. Analytics
 * failures are reported but never affect page delivery.
 */
class TrackVisitor
{
    public const string COOKIE = 'visitor_id';

    private const int COOKIE_MINUTES = 60 * 24 * 365;

    public function __construct(private readonly VisitorTracker $tracker) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->isEligible($request, $response) && ! Str::isUuid((string) $request->cookie(self::COOKIE))) {
            $token = (string) Str::uuid();
            $request->cookies->set(self::COOKIE, $token);
            $response->headers->setCookie(Cookie::make(self::COOKIE, $token, self::COOKIE_MINUTES, '/', null, $request->isSecure(), true, false, 'lax'));
        }

        return $response;
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! $this->isEligible($request, $response)) {
            return;
        }

        try {
            $this->tracker->record(
                token: (string) $request->cookie(self::COOKIE),
                path: $request->path(),
                ip: $request->ip(),
                userAgent: $request->userAgent(),
                referrer: $request->headers->get('referer'),
                userId: $request->user()?->getAuthIdentifier(),
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function isEligible(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET') || $response->getStatusCode() !== 200) {
            return false;
        }

        if (($request->expectsJson() || $request->ajax()) && ! $request->headers->has('X-Inertia')) {
            return false;
        }

        if ($request->headers->get('Purpose') === 'prefetch' || $request->headers->has('X-Inertia-Partial-Component')) {
            return false;
        }

        return ! $this->tracker->isExcludedPath($request->path());
    }
}
