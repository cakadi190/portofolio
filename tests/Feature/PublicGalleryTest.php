<?php

use App\Enums\CafeFacility;
use App\Models\CoffeePlace;
use App\Models\Portfolio;
use App\Models\PortfolioGallery;

test('the portfolio page exposes its gallery images for the lightbox', function () {
    $portfolio = Portfolio::factory()->create();
    PortfolioGallery::query()->create(['portfolio_id' => $portfolio->id, 'image_url' => 'media/2026/10/a.webp', 'description' => 'Beranda']);

    $this->get(route('portfolios.show', $portfolio))
        ->assertInertia(fn ($page) => $page
            ->has('portfolio.galleries', 1)
            ->where('portfolio.galleries.0.url', '/storage/media/2026/10/a.webp')
            ->where('portfolio.galleries.0.title', 'Beranda'));
});

test('the coffee place list exposes every detail needed by the modal', function () {
    $place = CoffeePlace::query()->create([
        'name' => 'Kopi Uji',
        'address' => 'Jl. Uji 1',
        'region' => 'Ngawi',
        'wifi_provider' => 'IndiHome',
        'wifi_speed' => 'strong',
        'price_tier' => 'cheap',
        'facilities' => [CafeFacility::Outdoor->value, CafeFacility::PowerOutlet->value],
        'is_recommended' => true,
    ]);
    $place->galleries()->create(['image_url' => 'media/2026/10/kopi.webp', 'description' => 'Teras']);

    $this->get('/sumber-daya/tempat-ngopi')
        ->assertInertia(fn ($page) => $page
            ->where('places.data.0.wifiProvider', 'IndiHome')
            ->where('places.data.0.wifiSpeed', 'Strong')
            ->where('places.data.0.priceTier', 'Cheap')
            ->where('places.data.0.facilities', ['Outdoor', 'Colokan Listrik'])
            ->where('places.data.0.galleries.0.url', '/storage/media/2026/10/kopi.webp'));
});
