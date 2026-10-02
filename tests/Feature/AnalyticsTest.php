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
