<?php

use App\Models\Media;
use App\Models\Portfolio;
use App\Models\PortfolioCategory;
use App\Models\Technology;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

test('guests are redirected to the login page', function () {
    $this->get(route('admin.portfolios.index'))->assertRedirect(route('login'));
});

test('a portfolio can be created with technologies and categories', function () {
    $technology = Technology::factory()->create();
    $category = PortfolioCategory::factory()->create();
    $media = Media::factory()->create();

    $response = $this->actingAs(User::factory()->create())->post(route('admin.portfolios.store'), [
        'name' => 'Sistem Informasi',
        'image' => $media->path,
        'technologies' => [$technology->id],
        'categories' => [$category->id],
    ]);

    $response->assertRedirect(route('admin.portfolios.index'));

    $portfolio = Portfolio::query()->firstOrFail();
    expect($portfolio->slug)->toBe('sistem-informasi');
    expect($portfolio->technologies)->toHaveCount(1);
    expect($portfolio->categories)->toHaveCount(1);
    expect($portfolio->image)->toBe($media->path);
});

test('creating a portfolio requires a name and image', function () {
    $response = $this->actingAs(User::factory()->create())
        ->post(route('admin.portfolios.store'), []);

    $response->assertSessionHasErrors(['name', 'image']);
});

test('updating a portfolio syncs its technologies', function () {
    $portfolio = Portfolio::factory()->create();
    $technologyA = Technology::factory()->create();
    $technologyB = Technology::factory()->create();
    $portfolio->technologies()->sync([$technologyA->id]);

    $response = $this->actingAs(User::factory()->create())->put(route('admin.portfolios.update', $portfolio), [
        'name' => $portfolio->name,
        'image' => $portfolio->image,
        'technologies' => [$technologyB->id],
    ]);

    $response->assertRedirect(route('admin.portfolios.index'));

    expect($portfolio->technologies()->pluck('technologies.id')->all())->toBe([$technologyB->id]);
});

test('a portfolio can be deleted while its image stays in the library', function () {
    $media = Media::factory()->create();
    Storage::fake('public');
    Storage::disk('public')->put($media->path, 'fake-content');
    $portfolio = Portfolio::factory()->create(['image' => $media->path]);

    $response = $this->actingAs(User::factory()->create())
        ->delete(route('admin.portfolios.destroy', $portfolio));

    $response->assertRedirect(route('admin.portfolios.index'));
    $this->assertDatabaseMissing('portfolios', ['id' => $portfolio->id]);
    Storage::disk('public')->assertExists($media->path);
});
