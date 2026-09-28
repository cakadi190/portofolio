<?php

use App\Models\Portfolio;
use App\Models\PortfolioCategory;
use App\Models\Technology;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

test('guests are redirected to the login page', function () {
    $this->get(route('admin.portfolios.index'))->assertRedirect(route('login'));
});

test('a portfolio can be created with technologies and categories', function () {
    $technology = Technology::factory()->create();
    $category = PortfolioCategory::factory()->create();

    $response = $this->actingAs(User::factory()->create())->post(route('admin.portfolios.store'), [
        'name' => 'Sistem Informasi',
        'image' => UploadedFile::fake()->image('cover.jpg'),
        'technologies' => [$technology->id],
        'categories' => [$category->id],
    ]);

    $response->assertRedirect(route('admin.portfolios.index'));

    $portfolio = Portfolio::query()->firstOrFail();
    expect($portfolio->slug)->toBe('sistem-informasi');
    expect($portfolio->technologies)->toHaveCount(1);
    expect($portfolio->categories)->toHaveCount(1);
    Storage::disk('public')->assertExists($portfolio->image);
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
        'technologies' => [$technologyB->id],
    ]);

    $response->assertRedirect(route('admin.portfolios.index'));

    expect($portfolio->technologies()->pluck('technologies.id')->all())->toBe([$technologyB->id]);
});

test('a portfolio can be deleted along with its image', function () {
    $portfolio = Portfolio::factory()->create(['image' => 'portfolios/cover.jpg']);
    Storage::disk('public')->put('portfolios/cover.jpg', 'fake-content');

    $response = $this->actingAs(User::factory()->create())
        ->delete(route('admin.portfolios.destroy', $portfolio));

    $response->assertRedirect(route('admin.portfolios.index'));
    $this->assertDatabaseMissing('portfolios', ['id' => $portfolio->id]);
    Storage::disk('public')->assertMissing('portfolios/cover.jpg');
});
