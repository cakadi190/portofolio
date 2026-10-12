<?php

use App\Services\HtmlSanitizer;

test('scripting and embedding elements are removed with their content', function (string $html) {
    expect(HtmlSanitizer::clean('<p>aman</p>'.$html))->toBe('<p>aman</p>');
})->with([
    'script' => '<script>alert(1)</script>',
    'iframe' => '<iframe src="https://evil.example"></iframe>',
    'svg' => '<svg onload="alert(1)"><circle/></svg>',
    'object' => '<object data="x"></object>',
    'form' => '<form action="/x"><input name="a"><button>go</button></form>',
    'meta refresh' => '<meta http-equiv="refresh" content="0;url=//evil.example">',
    'comment' => '<!-- <script>alert(1)</script> -->',
]);

test('event handlers and unknown attributes are stripped', function () {
    expect(HtmlSanitizer::clean('<img src="/storage/a.webp" alt="A" onerror="alert(1)" data-x="1">'))
        ->toBe('<img src="/storage/a.webp" alt="A">');
});

test('unsafe link and image schemes are rejected', function (string $html) {
    expect(HtmlSanitizer::clean($html))->not->toContain('javascript')->not->toContain('data:')->not->toContain('//evil');
})->with([
    'javascript' => '<a href="javascript:alert(1)">x</a>',
    'javascript with tab' => "<a href=\"java\tscript:alert(1)\">x</a>",
    'javascript entity' => '<a href="&#106;avascript:alert(1)">x</a>',
    'vbscript' => '<a href="vbscript:x">x</a>',
    'scheme-relative' => '<a href="//evil.example">x</a>',
    'data image' => '<img src="data:text/html;base64,AAAA">',
    'javascript image' => '<img src="javascript:alert(1)">',
]);

test('an image left without a source is dropped', function () {
    expect(HtmlSanitizer::clean('<p>a</p><img src="javascript:alert(1)">'))->toBe('<p>a</p>');
});

test('safe links keep their href and blank targets get a safe rel', function () {
    expect(HtmlSanitizer::clean('<a href="https://example.com" target="_blank">x</a>'))
        ->toBe('<a href="https://example.com" target="_blank" rel="noopener noreferrer">x</a>')
        ->and(HtmlSanitizer::clean('<a href="/blog/satu">x</a><a href="mailto:a@b.co">y</a>'))
        ->toBe('<a href="/blog/satu">x</a><a href="mailto:a@b.co">y</a>');
});

test('only harmless presentation styles survive', function () {
    expect(HtmlSanitizer::clean('<p style="color: red; background: url(javascript:x); text-align: center">x</p>'))
        ->toBe('<p style="color: red; text-align: center">x</p>')
        ->and(HtmlSanitizer::clean('<p style="color: expression(alert(1))">x</p>'))
        ->toBe('<p>x</p>');
});

test('class values that could break out of the attribute are dropped', function () {
    expect(HtmlSanitizer::clean('<p class="a&quot; onmouseover=&quot;x">x</p>'))->toBe('<p>x</p>');
});

test('unknown elements are unwrapped but their text is kept', function () {
    expect(HtmlSanitizer::clean('<custom-el onclick="x">teks <b>tebal</b></custom-el>'))->toBe('teks <b>tebal</b>');
});

test('the markup produced by the editor is preserved', function (string $html) {
    expect(HtmlSanitizer::clean($html))->toBe($html);
})->with([
    'image block' => '<figure class="wp-block-image is-align-center" style="width: 60%"><img src="/storage/media/a.webp" alt="A"><figcaption>Cap</figcaption></figure>',
    'callout' => '<div class="wp-block-callout is-info" data-type="info"><p>Hi</p></div>',
    'code block' => '<pre data-code-theme="dark"><code class="language-php">&lt;?php echo 1;</code></pre>',
    'table' => '<table><tbody><tr><td colspan="2">x</td></tr></tbody></table>',
    'text' => '<h2>Judul</h2><p>Klarifikasi — “tidak benar” é</p><ul><li>satu</li></ul><blockquote>kutipan</blockquote>',
]);

test('empty input yields an empty string', function () {
    expect(HtmlSanitizer::clean(null))->toBe('')->and(HtmlSanitizer::clean('  '))->toBe('');
});

test('code block title is kept but unsafe titles are dropped', function () {
    expect(HtmlSanitizer::clean('<pre data-code-title="app/Models/User.php"><code>x</code></pre>'))
        ->toContain('data-code-title="app/Models/User.php"')
        ->and(HtmlSanitizer::clean('<pre data-code-title="a&quot;onclick=&quot;x"><code>x</code></pre>'))
        ->not->toContain('data-code-title');
});
