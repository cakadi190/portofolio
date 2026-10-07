<?php

use App\Models\Media;
use App\Models\Portfolio;
use App\Models\Service;
use App\Models\Technology;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

test('guests are redirected to the login page', function () {
    $this->get(route('admin.portfolios.index'))->assertRedirect(route('login'));
});

test('a portfolio can be created with technologies and services', function () {
    $technology = Technology::factory()->create();
    $service = Service::factory()->create();
    $media = Media::factory()->create();

    $response = $this->actingAs(User::factory()->admin()->create())->post(route('admin.portfolios.store'), [
        'name' => 'Sistem Informasi',
        'image' => $media->path,
        'technologies' => [$technology->id],
        'services' => [$service->id],
    ]);

    $portfolio = Portfolio::query()->firstOrFail();

    $response->assertRedirect(route('admin.portfolios.edit', $portfolio));

    expect($portfolio->slug)->toBe('sistem-informasi');
    expect($portfolio->technologies)->toHaveCount(1);
    expect($portfolio->services)->toHaveCount(1);
    expect($portfolio->image)->toBe($media->path);
});

test('the editor pages render', function () {
    $portfolio = Portfolio::factory()->create();
    $user = User::factory()->admin()->create();

    $this->actingAs($user)->get(route('admin.portfolios.create'))->assertOk();
    $this->actingAs($user)->get(route('admin.portfolios.edit', $portfolio))->assertOk();
});

test('multiple gallery images are saved and replaced on update', function () {
    $cover = Media::factory()->create();
    [$a, $b, $c] = Media::factory()->count(3)->create();
    $user = User::factory()->admin()->create();

    $this->actingAs($user)->post(route('admin.portfolios.store'), [
        'name' => 'Galeri',
        'image' => $cover->path,
        'galleries' => [
            ['image_url' => $a->path, 'description' => 'Depan'],
            ['image_url' => $b->path, 'description' => null],
        ],
    ])->assertSessionHasNoErrors();

    $portfolio = Portfolio::query()->firstOrFail();
    expect($portfolio->galleries()->pluck('image_url')->all())->toBe([$a->path, $b->path]);

    $this->actingAs($user)->put(route('admin.portfolios.update', $portfolio), [
        'name' => 'Galeri',
        'image' => $cover->path,
        'galleries' => [['image_url' => $c->path]],
    ])->assertSessionHasNoErrors();

    expect($portfolio->galleries()->pluck('image_url')->all())->toBe([$c->path]);
});

test('gallery images must exist in the media library', function () {
    $cover = Media::factory()->create();

    $this->actingAs(User::factory()->admin()->create())->post(route('admin.portfolios.store'), [
        'name' => 'Galeri',
        'image' => $cover->path,
        'galleries' => [['image_url' => 'media/tidak-ada.webp']],
    ])->assertSessionHasErrors('galleries.0.image_url');
});

test('creating a portfolio requires a name and image', function () {
    $response = $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.portfolios.store'), []);

    $response->assertSessionHasErrors(['name', 'image']);
});

test('updating a portfolio syncs its technologies', function () {
    $portfolio = Portfolio::factory()->create();
    $technologyA = Technology::factory()->create();
    $technologyB = Technology::factory()->create();
    $portfolio->technologies()->sync([$technologyA->id]);

    $response = $this->actingAs(User::factory()->admin()->create())->put(route('admin.portfolios.update', $portfolio), [
        'name' => $portfolio->name,
        'image' => $portfolio->image,
        'technologies' => [$technologyB->id],
    ]);

    $response->assertRedirect(route('admin.portfolios.edit', $portfolio));

    expect($portfolio->technologies()->pluck('technologies.id')->all())->toBe([$technologyB->id]);
});

test('a portfolio can be deleted while its image stays in the library', function () {
    $media = Media::factory()->create();
    Storage::fake('public');
    Storage::disk('public')->put($media->path, 'fake-content');
    $portfolio = Portfolio::factory()->create(['image' => $media->path]);

    $response = $this->actingAs(User::factory()->admin()->create())
        ->delete(route('admin.portfolios.destroy', $portfolio));

    $response->assertRedirect(route('admin.portfolios.index'));
    $this->assertDatabaseMissing('portfolios', ['id' => $portfolio->id]);
    Storage::disk('public')->assertExists($media->path);
});
