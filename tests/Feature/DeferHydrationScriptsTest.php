<?php

use App\Http\Middleware\DeferHydrationScripts;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

const SAMPLE_HEAD_TAGS = '<link rel="modulepreload" as="script" href="https://x.test/build/assets/dep.js" />'
    .'<link rel="stylesheet" href="https://x.test/build/assets/app.css" />';

/**
 * Run a request through the middleware with a response shaped like Vite's output.
 */
function deferred(string $path, array $headers = []): Response
{
    $html = '<!DOCTYPE html><html><head>'.SAMPLE_HEAD_TAGS.'<script type="module" src="https://x.test/build/assets/app.js"></script></head><body><div id="root">SSR</div></body></html>';

    return (new DeferHydrationScripts)->handle(
        Request::create($path, server: collect($headers)->mapWithKeys(fn ($value, $name) => ['HTTP_'.strtoupper(str_replace('-', '_', $name)) => $value])->all()),
        fn () => response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8', 'Link' => '<https://x.test/build/assets/app.css>; rel="preload"; as="style", <https://x.test/build/assets/dep.js>; rel="modulepreload"; as="script"']),
    );
}

test('public pages load their scripts on first interaction instead of in the head', function () {
    $response = deferred('/portfolio');
    $html = $response->getContent();

    expect($html)->not->toContain('<script type="module"')
        ->not->toContain('rel="modulepreload"')
        ->toContain('<link rel="stylesheet" href="https://x.test/build/assets/app.css" />')
        ->toContain('<div id="root">SSR</div>')
        ->toContain('"s":[{"type":"module","src":"https://x.test/build/assets/app.js"}]')
        ->toContain('"p":[{"rel":"modulepreload","as":"script","href":"https://x.test/build/assets/dep.js"}]')
        ->toContain('["pointerdown","pointermove","keydown","scroll","touchstart"]')
        ->toContain('setTimeout(go,10000)');
    expect(strpos($html, 'function(c)'))->toBeLessThan(strpos($html, '</body>'));
});

test('only the script preload headers are dropped, style preloads stay', function () {
    $link = deferred('/')->headers->get('Link');

    expect($link)->toContain('rel="preload"; as="style"')->not->toContain('modulepreload');
});

test('admin, auth and resume pages keep their eager scripts', function (string $path) {
    $response = deferred($path);

    expect($response->getContent())->toContain('<script type="module" src="https://x.test/build/assets/app.js">')
        ->and($response->headers->get('Link'))->toContain('modulepreload');
})->with(['/admin', '/admin/posts', '/login', '/register', '/settings/profile', '/resume', '/dashboard']);

test('Inertia visits and non-HTML responses are left alone', function () {
    expect(deferred('/portfolio', ['X-Inertia' => 'true'])->getContent())->toContain('<script type="module"');

    $json = (new DeferHydrationScripts)->handle(Request::create('/portfolio'), fn () => response()->json(['a' => 1]));
    expect($json->getContent())->toBe('{"a":1}');
});
