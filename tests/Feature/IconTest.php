<?php

use App\Models\User;
use Illuminate\Support\Facades\Cache;
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
    Storage::fake('local');
    Http::fake([
        'api.iconify.design/search*' => Http::response(['icons' => ['devicon:laravel', 'bad id']]),
        'api.iconify.design/devicon.json*' => Http::response(['width' => 128, 'height' => 128, 'icons' => ['laravel' => ['body' => '<path d="M0 0h1v1H0z"/>']]]),
    ]);

    $this->getJson(route('admin.icons.search', ['query' => 'laravel']))->assertUnauthorized();

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->getJson(route('admin.icons.search', ['query' => 'laravel']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', 'devicon:laravel')
        ->assertJsonPath('data.0.url', '/icons/devicon/laravel.svg');

    $this->actingAs($admin)->getJson(route('admin.icons.search', ['query' => 'laravel']))->assertOk();
    Http::assertSentCount(2);
});

test('a page of search results is downloaded with one request per icon set', function () {
    Storage::fake('local');
    Cache::flush();
    Http::fake([
        'api.iconify.design/search*' => Http::response(['icons' => ['logos:sqlite', 'logos:laravel', 'devicon:sqlite']]),
        'api.iconify.design/logos.json*' => Http::response(['width' => 32, 'height' => 32, 'icons' => [
            'sqlite' => ['body' => '<path d="M0 0h1v1H0z"/>'],
            'laravel' => ['body' => '<path d="M0 0h2v2H0z"/>'],
        ]]),
        'api.iconify.design/devicon.json*' => Http::response(['width' => 128, 'height' => 128, 'icons' => ['sqlite' => ['body' => '<path d="M0 0h3v3H0z"/>']]]),
    ]);

    $this->actingAs(User::factory()->admin()->create())->getJson(route('admin.icons.search', ['query' => 'sqlite']))->assertOk();

    Http::assertSentCount(3);
    Storage::disk('local')->assertExists(['icons/logos/sqlite.svg', 'icons/logos/laravel.svg', 'icons/devicon/sqlite.svg']);
    $this->get('/icons/logos/sqlite.svg')->assertOk()->assertSee('viewBox="0 0 32 32"', false);
    Http::assertSentCount(3);
});

test('after a 429 the icon API is left alone until Retry-After has passed', function () {
    Storage::fake('local');
    Cache::flush();
    Http::fake(['api.iconify.design/*' => Http::response('Too Many Requests', 429, ['Retry-After' => '233'])]);

    $this->get('/icons/devicon/laravel.svg')->assertNotFound();
    $this->get('/icons/devicon/php.svg')->assertNotFound();

    expect(Http::recorded(fn ($request): bool => str_contains($request->url(), 'api.iconify.design')))->toHaveCount(1);
});
