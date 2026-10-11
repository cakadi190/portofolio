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
