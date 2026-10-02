<?php

use App\Models\SystemSetting;
use App\Services\SystemSettingService;
use App\Services\TurnstileService;
use Illuminate\Support\Facades\Http;

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
