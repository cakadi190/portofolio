<?php

test('web responses carry the baseline security headers', function () {
    $this->get(route('home'))
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(self)');
});

test('hsts is only sent over https', function () {
    $this->get(route('home'))->assertHeaderMissing('Strict-Transport-Security');

    $this->get(secure_url('/'))->assertHeader('Strict-Transport-Security', 'max-age=31536000');
});

test('a report-only content security policy is sent without blocking anything', function () {
    $response = $this->get(route('home'));

    expect($response->headers->get('Content-Security-Policy-Report-Only'))
        ->toContain("default-src 'self'")
        ->toContain("object-src 'none'")
        ->toContain('https://challenges.cloudflare.com');

    $response->assertHeaderMissing('Content-Security-Policy');
});

test('the content security policy is enforced with a per-request nonce instead of unsafe-inline scripts', function () {
    config(['app.csp_enforce' => true]);

    $response = $this->get(route('home'));
    $policy = $response->headers->get('Content-Security-Policy');

    preg_match("/script-src [^;]*'nonce-([A-Za-z0-9+\/=]+)'/", (string) $policy, $match);

    expect($match)->toHaveCount(2);
    expect($policy)->not->toMatch("/script-src [^;]*'unsafe-inline'/");

    $response->assertHeaderMissing('Content-Security-Policy-Report-Only');
    expect($response->headers->get('Cross-Origin-Opener-Policy'))->toBe('same-origin-allow-popups');

    $other = $this->get(route('home'))->headers->get('Content-Security-Policy');
    expect($other)->not->toBe($policy);
});

test('every inline script on a page carries the nonce announced in the policy', function () {
    config(['app.csp_enforce' => true]);

    $response = $this->get(route('home'));
    preg_match("/'nonce-([^']+)'/", (string) $response->headers->get('Content-Security-Policy'), $match);

    preg_match_all('#<script(?![^>]*\bsrc=)(?![^>]*type="application/(?:json|ld\+json)")([^>]*)>#i', $response->getContent(), $inline);

    expect($inline[1])->not->toBeEmpty();

    foreach ($inline[1] as $attributes) {
        expect($attributes)->toContain('nonce="'.$match[1].'"');
    }
});
