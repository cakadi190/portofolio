<?php

use App\Models\CoffeePlace;
use App\Models\Media;
use App\Models\User;

/**
 * @return array<string, mixed>
 */
function coffeePlacePayload(array $overrides = []): array
{
    return [
        'name' => 'Kopi Senja',
        'address' => 'Jl. Merdeka 1',
        'wifi_speed' => 'strong',
        'price_tier' => 'medium',
        ...$overrides,
    ];
}

test('a coffee place stores facilities and galleries', function () {
    $media = Media::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.coffee-places.store'), coffeePlacePayload([
            'facilities' => ['outdoor', 'wfc', 'ac'],
            'galleries' => [['image_url' => $media->path, 'description' => 'Teras']],
        ]))
        ->assertRedirect(route('admin.coffee-places.index'));

    $place = CoffeePlace::query()->firstOrFail();

    expect($place->facilities)->toBe(['outdoor', 'wfc', 'ac']);
    expect($place->galleries)->toHaveCount(1);
    expect($place->galleries->first()->image_url)->toBe($media->path);
});

test('updating replaces the galleries', function () {
    $place = CoffeePlace::query()->create(coffeePlacePayload());
    $place->galleries()->create(['image_url' => Media::factory()->create()->path]);
    $media = Media::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.coffee-places.update', $place), coffeePlacePayload([
            'galleries' => [['image_url' => $media->path]],
        ]))
        ->assertRedirect();

    expect($place->galleries()->pluck('image_url')->all())->toBe([$media->path]);
});

test('unknown facilities are rejected', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.coffee-places.store'), coffeePlacePayload(['facilities' => ['helipad']]))
        ->assertSessionHasErrors('facilities.0');
});
