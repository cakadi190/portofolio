<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds baseline browser hardening headers to every web response.
 *
 * The Content-Security-Policy is enforced in production (`app.csp_enforce`) and
 * report-only elsewhere. Inline scripts run only with the per-request nonce (shared
 * with Vite's tags through `Vite::useCspNonce()`), so injected markup cannot execute
 * script even if it slips past the HTML sanitizer.
 */
class SecurityHeaders
{
    /**
     * Tesseract.js (OCR in the PDF viewers) compiles WebAssembly and fetches its core and language data from jsDelivr.
     */
    private const CONTENT_SECURITY_POLICY = "default-src 'self'; "
        ."script-src 'self' 'nonce-{nonce}' 'wasm-unsafe-eval' https://www.googletagmanager.com https://challenges.cloudflare.com https://cdn.jsdelivr.net https://cloud.umami.is; "
        ."style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
        ."font-src 'self' data: https://fonts.gstatic.com; "
        ."img-src 'self' data: blob: https:; "
        ."media-src 'self' blob: https:; "
        ."connect-src 'self' data: blob: https://cdn.jsdelivr.net https://api.iconify.design https://api.simplesvg.com https://api.unisvg.com https://www.google-analytics.com https://*.google-analytics.com https://*.analytics.google.com https://www.googletagmanager.com https://cloud.umami.is https://api-gateway.umami.dev https://*.ingest.sentry.io https://*.ingest.us.sentry.io https://*.ingest.de.sentry.io; "
        .'frame-src https://challenges.cloudflare.com https://www.youtube-nocookie.com https://www.youtube.com https://player.vimeo.com https://www.vidio.com https://www.dailymotion.com https://www.figma.com; '
        ."worker-src 'self' blob:; "
        ."object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'";

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Vite::useCspNonce();

        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(self)');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');
        $response->headers->set(
            $this->enforced() ? 'Content-Security-Policy' : 'Content-Security-Policy-Report-Only',
            $this->policy($nonce),
        );

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        return $response;
    }

    /**
     * The base policy, widened with the Vite dev server origin while it is running so hot reloading stays quiet.
     */
    private function policy(string $nonce): string
    {
        $base = str_replace('{nonce}', $nonce, self::CONTENT_SECURITY_POLICY);
        $hot = public_path('hot');

        if (! is_file($hot)) {
            return $base;
        }

        $origin = rtrim(trim((string) file_get_contents($hot)), '/');
        $socket = preg_replace('#^http#', 'ws', $origin);

        return (string) preg_replace(
            ['/(script-src|style-src|font-src|img-src) /', '/connect-src /'],
            ['$1 '.$origin.' ', 'connect-src '.$origin.' '.$socket.' '],
            $base,
        );
    }

    /**
     * Never enforced while the Vite dev server runs: it injects inline scripts of its own.
     */
    private function enforced(): bool
    {
        return (bool) config('app.csp_enforce') && ! Vite::isRunningHot();
    }
}
