<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Give every request a correlation id, shared by activity logs and the
 * `X-Request-Id` response header.
 */
class AssignRequestId
{
    public const string ATTRIBUTE = 'request_id';

    public function handle(Request $request, Closure $next): Response
    {
        $incoming = (string) $request->header('X-Request-Id');
        $requestId = Str::isUuid($incoming) ? $incoming : (string) Str::uuid();

        $request->attributes->set(self::ATTRIBUTE, $requestId);

        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}
