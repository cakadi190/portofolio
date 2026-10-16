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
    'image with credit and source' => '<figure class="wp-block-image is-align-center" data-source="https://src.id/p"><img src="/a.webp"><figcaption data-credit="Workwear Archive">Cap</figcaption></figure>',
    'link card' => '<a class="wp-block-linkcard" href="https://x.id/p" target="_blank" rel="noopener noreferrer"><img class="wp-linkcard-image" src="https://x.id/a.png" alt="" loading="lazy"><span class="wp-linkcard-meta"><span class="wp-linkcard-site">x.id</span><strong class="wp-linkcard-title">T</strong></span></a>',
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

test('article blocks keep their markup and equations stay escaped text', function (string $html) {
    expect(HtmlSanitizer::clean($html))->toBe($html);
})->with([
    'callout with header' => '<div class="wp-block-callout is-info" data-type="info"><div class="wp-callout-head"><span class="wp-callout-label">Ekonomi</span><strong class="wp-callout-title">Biaya</strong></div><div class="wp-callout-body"><p>isi</p></div></div>',
    'definition card' => '<div class="wp-block-definition"><div class="wp-definition-head"><strong class="wp-definition-term">realm</strong><span class="wp-definition-phonetic">/rɛlm/</span></div><div class="wp-definition-body"><p>wilayah</p></div></div>',
    'spec sheet' => '<div class="wp-block-spec"><div class="wp-spec-title">Uniform</div><div class="wp-spec-rows"><div class="wp-spec-row"><div class="wp-spec-label">Bobot</div><div class="wp-spec-value">280 GSM</div></div></div></div>',
    'references' => '<div class="wp-block-references"><p class="wp-reference"><strong>A</strong> <a href="https://doi.org/10.1/x">DOI</a></p></div>',
    'equations' => '<p>Teks <span class="wp-math">a &lt; b</span></p><div class="wp-math-block">E = mc^2</div>',
]);

test('editor-only attributes on article blocks are stripped', function () {
    expect(HtmlSanitizer::clean('<div class="wp-callout-head" contenteditable="false" onclick="x()"><strong>T</strong></div>'))
        ->toBe('<div class="wp-callout-head"><strong>T</strong></div>');
});

test('markup inside an equation is not executable', function () {
    expect(HtmlSanitizer::clean('<span class="wp-math">&lt;img src=x onerror=alert(1)&gt;</span>'))
        ->not->toContain('<img');
});

test('an unsafe figure source link is dropped', function () {
    expect(HtmlSanitizer::clean('<figure data-source="javascript:alert(1)"><img src="/a.webp"></figure>'))
        ->not->toContain('data-source');
});

test('code block keeps the title-tab and line-number switches but only the value 1', function () {
    expect(HtmlSanitizer::clean('<pre data-code-title-hidden="1" data-code-lines="1"><code>x</code></pre>'))
        ->toBe('<pre data-code-title-hidden="1" data-code-lines="1"><code>x</code></pre>')
        ->and(HtmlSanitizer::clean('<pre data-code-lines="yes" data-code-title-hidden="0"><code>x</code></pre>'))
        ->toBe('<pre><code>x</code></pre>');
});
