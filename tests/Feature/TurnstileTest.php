<?php

use App\Http\Middleware\VerifyTurnstile;
use App\Models\BlogComment;
use App\Models\Post;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\SystemSettingService;
use App\Services\TurnstileService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

function enableTurnstile(): void
{
    SystemSetting::factory()->create(['key' => 'turnstile_site_key', 'value' => 'site-key']);
    SystemSetting::factory()->create(['key' => 'turnstile_secret_key', 'value' => 'secret-key']);
    app(SystemSettingService::class)->forget();
}

function contactPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Budi',
        'email' => 'budi@example.com',
        'reason' => 'project_collaboration',
        'message' => '<p>Halo</p>',
    ], $overrides);
}

test('forms are not challenged while turnstile is not configured', function () {
    Http::fake();

    $this->post(route('contact.store'), contactPayload())->assertSessionDoesntHaveErrors();

    Http::assertNothingSent();
});

test('the site key is shared only when turnstile is enabled', function () {
    $this->get(route('contact.index'))->assertInertia(fn ($page) => $page->where('turnstileSiteKey', null));

    enableTurnstile();

    $this->get(route('contact.index'))->assertInertia(fn ($page) => $page->where('turnstileSiteKey', 'site-key'));
});

test('a submission without a token is rejected', function () {
    enableTurnstile();
    Http::fake();

    $this->post(route('contact.store'), contactPayload())
        ->assertSessionHasErrors(TurnstileService::RESPONSE_FIELD);

    $this->post(route('login.store'), ['email' => 'a@example.com', 'password' => 'x'])
        ->assertSessionHasErrors(TurnstileService::RESPONSE_FIELD);
});

test('a submission cloudflare rejects is refused', function () {
    enableTurnstile();
    Http::fake([TurnstileService::VERIFY_URL => Http::response(['success' => false])]);

    $this->post(route('contact.store'), contactPayload([TurnstileService::RESPONSE_FIELD => 'bad']))
        ->assertSessionHasErrors(TurnstileService::RESPONSE_FIELD);
});

test('verification fails closed when cloudflare is unreachable', function () {
    enableTurnstile();
    Http::fake([TurnstileService::VERIFY_URL => Http::response('', 500)]);

    $this->post(route('contact.store'), contactPayload([TurnstileService::RESPONSE_FIELD => 'token']))
        ->assertSessionHasErrors(TurnstileService::RESPONSE_FIELD);
});

test('a valid token lets the submission through', function () {
    enableTurnstile();
    Http::fake([TurnstileService::VERIFY_URL => Http::response(['success' => true])]);

    $this->post(route('contact.store'), contactPayload([TurnstileService::RESPONSE_FIELD => 'good']))
        ->assertSessionDoesntHaveErrors();

    Http::assertSent(fn ($request) => $request['secret'] === 'secret-key' && $request['response'] === 'good');
});

test('an expired or reused token is refused', function () {
    enableTurnstile();
    Http::fake([TurnstileService::VERIFY_URL => Http::response(['success' => false, 'error-codes' => ['timeout-or-duplicate']])]);

    $this->post(route('contact.store'), contactPayload([TurnstileService::RESPONSE_FIELD => 'old']))
        ->assertSessionHasErrors(TurnstileService::RESPONSE_FIELD);
});

test('json requests are verified too', function () {
    enableTurnstile();
    Http::fake();

    $this->postJson(route('contact.store'), contactPayload())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(TurnstileService::RESPONSE_FIELD);
});

test('blog comments need a valid token', function () {
    enableTurnstile();
    $post = Post::factory()->create();
    $user = User::factory()->create();

    Http::fake([TurnstileService::VERIFY_URL => Http::sequence()
        ->push(['success' => false])
        ->push(['success' => true])]);
    $this->actingAs($user)->post(route('blog.comments.store', $post), ['body' => 'Halo', TurnstileService::RESPONSE_FIELD => 'bad'])
        ->assertSessionHasErrors(TurnstileService::RESPONSE_FIELD);
    expect(BlogComment::count())->toBe(0);

    $this->actingAs($user)->post(route('blog.comments.store', $post), ['body' => 'Halo', TurnstileService::RESPONSE_FIELD => 'good'])
        ->assertSessionDoesntHaveErrors();
    expect(BlogComment::count())->toBe(1);
});

test('every public POST route is challenged', function () {
    // Passkey login is a WebAuthn signature and the two-factor challenge only
    // follows a Turnstile-checked login, so neither is a human-facing form.
    $exempt = ['passkey.login', 'two-factor.login.store'];

    $public = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => in_array('POST', $route->methods(), true) && $route->getName())
        ->reject(fn ($route) => str_starts_with($route->getName(), 'boost.') || in_array($route->getName(), $exempt, true))
        ->reject(fn ($route) => collect($route->gatherMiddleware())->contains(fn ($middleware) => is_string($middleware)
            && (str_starts_with($middleware, 'auth') || str_starts_with($middleware, 'can:'))))
        ->map(fn ($route) => $route->getName());

    expect($public->diff(VerifyTurnstile::PROTECTED_ROUTES)->values()->all())->toBe([]);
});
