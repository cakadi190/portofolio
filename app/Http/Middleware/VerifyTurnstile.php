<?php

namespace App\Http\Middleware;

use App\Services\TurnstileService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class VerifyTurnstile
{
    /**
     * Public form submissions guarded by Turnstile.
     *
     * @var list<string>
     */
    public const array PROTECTED_ROUTES = [
        'login.store',
        'register.store',
        'password.email',
        'password.update',
        'contact.store',
        'portfolios.reviews.store',
    ];

    public function __construct(private TurnstileService $turnstile) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->turnstile->isEnabled() && $request->isMethod('POST') && $request->routeIs(self::PROTECTED_ROUTES)) {
            if (! $this->turnstile->verify($request->input(TurnstileService::RESPONSE_FIELD), $request->ip())) {
                throw ValidationException::withMessages([
                    TurnstileService::RESPONSE_FIELD => 'Verifikasi keamanan gagal. Silakan coba lagi.',
                ]);
            }
        }

        return $next($request);
    }
}
