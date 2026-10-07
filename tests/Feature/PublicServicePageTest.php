<?php

use App\Models\Portfolio;
use App\Models\Service;

test('the services page lists services', function () {
    Service::factory()->create(['name' => 'Website']);

    $this->get(route('services.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('service/index')
            ->has('services', 1)
            ->where('services.0.name', 'Website'));
});

test('the service detail page lists only its portfolios', function () {
    $service = Service::factory()->create();
    $mine = Portfolio::factory()->create();
    $other = Portfolio::factory()->create();
    $service->portfolios()->attach($mine);

    $this->get(route('services.show', $service))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('service/show')
            ->where('service.slug', $service->slug)
            ->has('portfolios', 1)
            ->where('portfolios.0.slug', $mine->slug));

    expect($other->services)->toBeEmpty();
});

test('an unknown service slug returns 404', function () {
    $this->get('/layanan/tidak-ada')->assertNotFound();
});

test('the portfolio list exposes each portfolio services', function () {
    $service = Service::factory()->create(['name' => 'Mobile']);
    Portfolio::factory()->create()->services()->attach($service);

    $this->get(route('portfolios.index'))
        ->assertInertia(fn ($page) => $page->where('portfolios.data.0.services.0.name', 'Mobile'));
});
