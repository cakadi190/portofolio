<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Starts downloading the JavaScript bundle only after the page has loaded.
 *
 * Public pages are server-rendered, so the HTML, CSS and LCP photo are enough to
 * show the page; the ~150 KB of module scripts (and their `modulepreload`
 * hints) only add hydration. Requesting them in the head makes them compete
 * with the LCP photo for bandwidth and counts them towards FCP/LCP in
 * PageSpeed Insights. This moves them into a small loader that runs after the
 * `load` event (with a time limit), preserving the preload-then-execute order.
 *
 * Authenticated and form-heavy areas keep the eager scripts.
 */
class DeferHydrationScripts
{
    /**
     * Path patterns served with eager scripts.
     *
     * @var list<string>
     */
    private const array EAGER_PATHS = [
        'admin', 'admin/*', 'dashboard', 'dashboard/*', 'settings', 'settings/*', 'login', 'register',
        'forgot-password', 'reset-password/*', 'confirm-password', 'two-factor-challenge', 'user/*',
        'email/*', 'resume', 'resume/*', 'resume.pdf', 'certifications/*',
    ];

    /**
     * Start loading anyway after this many milliseconds, even if `load` is still pending.
     */
    private const int FALLBACK_MS = 4000;

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldDefer($request, $response)) {
            return $response;
        }

        $html = (string) $response->getContent();

        preg_match_all('#<link rel="modulepreload"[^>]*>#', $html, $preloads);
        preg_match_all('#<script type="module"[^>]*>\s*</script>#', $html, $scripts);

        if ($scripts[0] === []) {
            return $response;
        }

        $html = str_replace([...$preloads[0], ...$scripts[0]], '', $html);
        $loader = '<script>'.$this->loader($preloads[0], $scripts[0]).'</script>';

        $response->setContent(
            str_contains($html, '</body>')
                ? str_replace('</body>', $loader.'</body>', $html)
                : $html.$loader,
        );

        $this->dropModulePreloadHeaders($response);

        return $response;
    }

    private function shouldDefer(Request $request, Response $response): bool
    {
        return $response->isSuccessful()
            && str_contains((string) $response->headers->get('Content-Type'), 'text/html')
            && ! $request->header('X-Inertia')
            && ! $request->is(self::EAGER_PATHS)
            && ! Vite::isRunningHot();
    }

    /**
     * @param  list<string>  $preloadTags
     * @param  list<string>  $scriptTags
     */
    private function loader(array $preloadTags, array $scriptTags): string
    {
        $config = json_encode([
            'p' => array_map($this->attributes(...), $preloadTags),
            's' => array_map($this->attributes(...), $scriptTags),
        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES);

        $fallback = self::FALLBACK_MS;

        return '(function(c){var d=document,done=0;function add(t,a){var n=d.createElement(t);for(var k in a){n.setAttribute(k,a[k])}d.head.appendChild(n)}'
            ."function go(){if(done){return}done=1;c.p.forEach(function(a){add('link',a)});c.s.forEach(function(a){add('script',a)})}"
            ."if(d.readyState==='complete'){setTimeout(go,0)}else{addEventListener('load',function(){setTimeout(go,0)})}"
            ."setTimeout(go,{$fallback})})({$config})";
    }

    /**
     * @return array<string, string>
     */
    private function attributes(string $tag): array
    {
        preg_match_all('/([a-z][a-z0-9-]*)="([^"]*)"/i', $tag, $matches, PREG_SET_ORDER);

        return collect($matches)->mapWithKeys(fn (array $match): array => [$match[1] => html_entity_decode($match[2])])->all();
    }

    /**
     * Vite's preload `Link` headers would still fetch the scripts early, so keep only the non-script ones.
     */
    private function dropModulePreloadHeaders(Response $response): void
    {
        $entries = collect($response->headers->all('link'))
            ->flatMap(fn (string $header): array => preg_split('/,\s*(?=<)/', $header) ?: [])
            ->reject(fn (string $entry): bool => str_contains($entry, 'modulepreload'))
            ->values();

        $response->headers->remove('Link');

        if ($entries->isNotEmpty()) {
            $response->headers->set('Link', $entries->implode(', '));
        }
    }
}
