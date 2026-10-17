<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

const FAKE_SVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"><path d="M0 0h1v1H0z"/></svg>';

test('an icon is fetched once, then served from the local copy', function () {
    Storage::fake('local');
    Http::fake(['api.iconify.design/*' => Http::response(FAKE_SVG)]);

    $this->get('/icons/devicon/laravel.svg')
        ->assertOk()
        ->assertHeader('Content-Type', 'image/svg+xml')
        ->assertHeader('Cache-Control', 'immutable, max-age=31536000, public');

    $this->get('/icons/devicon/laravel.svg')->assertOk();

    Storage::disk('local')->assertExists('icons/devicon/laravel.svg');
    Http::assertSentCount(1);
});

test('an icon that is not an svg is never stored', function () {
    Storage::fake('local');
    Http::fake(['api.iconify.design/*' => Http::response('<html>nope</html>')]);

    $this->get('/icons/devicon/laravel.svg')->assertNotFound();

    Storage::disk('local')->assertMissing('icons/devicon/laravel.svg');
});

test('icon search returns cached results for admins only', function () {
    Http::fake(['api.iconify.design/search*' => Http::response(['icons' => ['devicon:laravel', 'bad id']])]);

    $this->getJson(route('admin.icons.search', ['query' => 'laravel']))->assertUnauthorized();

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->getJson(route('admin.icons.search', ['query' => 'laravel']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', 'devicon:laravel')
        ->assertJsonPath('data.0.url', '/icons/devicon/laravel.svg');

    $this->actingAs($admin)->getJson(route('admin.icons.search', ['query' => 'laravel']))->assertOk();
    Http::assertSentCount(1);
});
