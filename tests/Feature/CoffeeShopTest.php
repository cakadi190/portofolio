<?php

use App\Models\CoffeePlace;

/**
 * @param  array<string, mixed>  $overrides
 */
function coffeePlace(string $name, ?string $region, array $overrides = []): CoffeePlace
{
    return CoffeePlace::query()->create([
        'name' => $name,
        'address' => 'Jl. Contoh 1',
        'region' => $region,
        'wifi_speed' => 'strong',
        'price_tier' => 'medium',
        ...$overrides,
    ]);
}

test('the coffee shop list shows every place and the available regions', function () {
    coffeePlace('Kopi Senja', 'Ngawi');
    coffeePlace('Warkop Pagi', 'Madiun');

    $this->get(route('resources.coffee-shops.index'))->assertInertia(fn ($page) => $page
        ->component('sumber-daya/tempat-ngopi')
        ->has('places.data', 2)
        ->where('regions', ['Madiun', 'Ngawi'])
        ->where('filters', ['region' => null, 'search' => null]));
});

test('the region filter narrows the list to that region', function () {
    coffeePlace('Kopi Senja', 'Ngawi');
    coffeePlace('Warkop Pagi', 'Madiun');

    $this->get(route('resources.coffee-shops.index', ['region' => 'Madiun']))->assertInertia(fn ($page) => $page
        ->has('places.data', 1)
        ->where('places.data.0.name', 'Warkop Pagi')
        ->where('filters.region', 'Madiun'));
});

test('the search matches a place name or its region and combines with the region filter', function () {
    coffeePlace('Kopi Senja', 'Ngawi');
    coffeePlace('Senja Kota', 'Madiun');
    coffeePlace('Warkop Pagi', 'Madiun');

    $this->get(route('resources.coffee-shops.index', ['search' => 'senja']))->assertInertia(fn ($page) => $page->has('places.data', 2));

    $this->get(route('resources.coffee-shops.index', ['search' => 'madiun']))->assertInertia(fn ($page) => $page->has('places.data', 2));

    $this->get(route('resources.coffee-shops.index', ['region' => 'Madiun', 'search' => 'senja']))->assertInertia(fn ($page) => $page
        ->has('places.data', 1)
        ->where('places.data.0.name', 'Senja Kota'));
});

test('blank filter values are ignored and an unmatched search shows no places', function () {
    coffeePlace('Kopi Senja', 'Ngawi');

    $this->get(route('resources.coffee-shops.index', ['region' => '', 'search' => '  ']))->assertInertia(fn ($page) => $page->has('places.data', 1));

    $this->get(route('resources.coffee-shops.index', ['search' => 'tidak-ada']))->assertInertia(fn ($page) => $page->has('places.data', 0));
});
