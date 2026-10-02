<?php

use App\Http\Middleware\MinifyHtmlResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

function minifiedResponse(string $html, bool $production): Response
{
    app()->detectEnvironment(fn () => $production ? 'production' : 'local');

    return (new MinifyHtmlResponse)->handle(
        Request::create('/'),
        fn () => new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']),
    );
}

test('html is minified in production while keeping sensitive regions intact', function () {
    $html = "<div>\n    <!-- note -->\n    <p>Halo   dunia</p>\n    <pre>a   b\n  c</pre>\n</div>";

    $content = minifiedResponse($html, true)->getContent();

    expect($content)
        ->toBe('<div><p>Halo dunia</p><pre>a   b'."\n".'  c</pre></div>');
});

test('html is left untouched outside production', function () {
    $html = "<div>\n    <p>Halo</p>\n</div>";

    expect(minifiedResponse($html, false)->getContent())->toBe($html);
});

test('non html responses are not minified', function () {
    app()->detectEnvironment(fn () => 'production');
    $json = '{"a":  1}';

    $response = (new MinifyHtmlResponse)->handle(
        Request::create('/'),
        fn () => new Response($json, 200, ['Content-Type' => 'application/json']),
    );

    expect($response->getContent())->toBe($json);
});
