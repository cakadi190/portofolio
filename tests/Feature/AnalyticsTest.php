<?php

use App\Models\SystemSetting;

test('the google tag is rendered when a measurement id is configured', function () {
    config(['services.google_analytics.measurement_id' => 'G-TEST123456']);

    $this->get('/')
        ->assertOk()
        ->assertSee('https://www.googletagmanager.com/gtag/js?id=G-TEST123456', escape: false)
        ->assertSee('send_page_view', escape: false);
});

test('the google tag is omitted when no measurement id is configured', function () {
    config(['services.google_analytics.measurement_id' => null]);

    $this->get('/')
        ->assertOk()
        ->assertDontSee('googletagmanager.com', escape: false);
});

test('a measurement id saved in system settings takes precedence over the environment', function () {
    config(['services.google_analytics.measurement_id' => 'G-FROMENV']);
    SystemSetting::factory()->create(['key' => 'google_analytics_id', 'value' => 'G-FROMDB']);

    $this->get('/')->assertSee('G-FROMDB', escape: false)->assertDontSee('G-FROMENV', escape: false);
});

test('the sitemap stylesheet carries the google tag and sitemap events', function () {
    config(['services.google_analytics.measurement_id' => 'G-TEST123456']);

    $this->get(route('sitemaps.style'))
        ->assertOk()
        ->assertSee('googletagmanager.com/gtag/js?id=G-TEST123456', escape: false)
        ->assertSee("track('search'", escape: false)
        ->assertSee("'select_content'", escape: false);
});

test('the sitemap stylesheet omits the google tag without a measurement id', function () {
    config(['services.google_analytics.measurement_id' => null]);

    $this->get(route('sitemaps.style'))
        ->assertOk()
        ->assertDontSee('googletagmanager.com', escape: false);
});

test('the umami tracker is rendered and allowed by the content security policy', function () {
    $response = $this->get('/')
        ->assertOk()
        ->assertSee('https://cloud.umami.is/script.js', escape: false)
        ->assertSee('10c805e0-d6c0-48c3-bb4c-2237269955ed', escape: false);

    $policy = $response->headers->get('Content-Security-Policy')
        ?? $response->headers->get('Content-Security-Policy-Report-Only');

    expect($policy)->toContain('script-src', 'https://cloud.umami.is', 'https://api-gateway.umami.dev', 'https://gateway.umami.is');
});
