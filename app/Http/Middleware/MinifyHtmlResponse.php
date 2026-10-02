<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Collapses insignificant whitespace and strips comments from HTML responses
 * in production. Whitespace-sensitive regions (pre, textarea, script, style
 * and the Inertia `data-page` payload) are left untouched.
 */
class MinifyHtmlResponse
{
    private const PROTECTED_PATTERN = '#<(pre|textarea|script|style)\b[^>]*>.*?</\1>|data-page="[^"]*"|data-page=\'[^\']*\'#is';

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! app()->isProduction() || ! $this->isMinifiable($response)) {
            return $response;
        }

        $content = $response->getContent();

        if (! is_string($content) || $content === '') {
            return $response;
        }

        $response->setContent($this->minify($content));

        return $response;
    }

    public function minify(string $html): string
    {
        $preserved = [];

        $html = preg_replace_callback(self::PROTECTED_PATTERN, function (array $match) use (&$preserved): string {
            $preserved[] = $match[0];

            return '<!--keep:'.(count($preserved) - 1).'-->';
        }, $html) ?? $html;

        $html = preg_replace('/<!--(?!keep:)(?!\[if).*?-->/s', '', $html) ?? $html;
        $html = preg_replace('/\s+/', ' ', $html) ?? $html;
        $html = preg_replace('/>\s+</', '><', $html) ?? $html;

        return preg_replace_callback(
            '/<!--keep:(\d+)-->/',
            fn (array $match): string => $preserved[(int) $match[1]],
            trim($html),
        ) ?? $html;
    }

    private function isMinifiable(Response $response): bool
    {
        return $response->isSuccessful()
            && ! $response->headers->has('Content-Disposition')
            && str_contains((string) $response->headers->get('Content-Type'), 'text/html');
    }
}
