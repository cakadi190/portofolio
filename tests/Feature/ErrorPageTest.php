<?php

use App\Services\ErrorPageService;
use Inertia\Testing\AssertableInertia as Assert;

test('an unknown public url renders the inertia error page with a 404 status', function () {
    $this->get('/halaman-yang-tidak-ada')
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page
            ->component('error')
            ->where('status', 404)
            ->where('image', '/images/errors/404.svg'));
});

test('json requests keep the default error response', function () {
    $this->getJson('/halaman-yang-tidak-ada')
        ->assertNotFound()
        ->assertJsonMissingPath('component');
});

test('every mapped status has a copied illustration', function (int $status) {
    $props = app(ErrorPageService::class)->propsFor($status);

    expect(public_path($props['image']))->toBeFile();
})->with([400, 401, 403, 404, 419, 429, 500, 503]);

test('unmapped statuses have no dedicated page', function () {
    expect(app(ErrorPageService::class)->propsFor(418))->toBeNull();
});
