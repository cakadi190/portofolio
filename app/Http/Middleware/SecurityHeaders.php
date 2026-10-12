<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds baseline browser hardening headers to every web response.
 *
 * The Content-Security-Policy is sent as report-only: the site relies on
 * inline bootstrap data, Google Analytics, Turnstile and the Iconify API, so
 * violations are observed in the browser console before it is enforced.
 */
class SecurityHeaders
{
    /**
     * Tesseract.js (OCR in the PDF viewers) compiles WebAssembly and fetches its core and language data from jsDelivr.
     */
    private const CONTENT_SECURITY_POLICY = "default-src 'self'; "
        ."script-src 'self' 'unsafe-inline' 'wasm-unsafe-eval' https://www.googletagmanager.com https://challenges.cloudflare.com https://cdn.jsdelivr.net; "
        ."style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
        ."font-src 'self' data: https://fonts.gstatic.com; "
        ."img-src 'self' data: blob: https:; "
        ."media-src 'self' blob: https:; "
        ."connect-src 'self' data: blob: https://cdn.jsdelivr.net https://api.iconify.design https://api.simplesvg.com https://api.unisvg.com https://www.google-analytics.com https://*.google-analytics.com https://*.analytics.google.com https://www.googletagmanager.com; "
        .'frame-src https://challenges.cloudflare.com https://www.youtube-nocookie.com https://www.youtube.com https://player.vimeo.com https://www.vidio.com https://www.dailymotion.com https://www.figma.com; '
        ."worker-src 'self' blob:; "
        ."object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'";

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(self)');
        $response->headers->set('Content-Security-Policy-Report-Only', $this->policy());

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        return $response;
    }

    /**
     * The base policy, widened with the Vite dev server origin while it is running so hot reloading stays quiet.
     */
    private function policy(): string
    {
        $hot = public_path('hot');

        if (! is_file($hot)) {
            return self::CONTENT_SECURITY_POLICY;
        }

        $origin = rtrim(trim((string) file_get_contents($hot)), '/');
        $socket = preg_replace('#^http#', 'ws', $origin);

        return (string) preg_replace(
            ['/(script-src|style-src|font-src|img-src) /', '/connect-src /'],
            ['$1 '.$origin.' ', 'connect-src '.$origin.' '.$socket.' '],
            self::CONTENT_SECURITY_POLICY,
        );
    }
}
