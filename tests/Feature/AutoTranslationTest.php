<?php

use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    config(['services.anthropic.key' => 'test-key', 'services.anthropic.model' => 'claude-opus-5-5']);
});

function translate(array $payload = []): TestResponse
{
    return test()->actingAs(User::factory()->admin()->create())->postJson(route('admin.translate'), [
        'text' => '<p>Halo <strong>dunia</strong></p>',
        'from' => 'id',
        'to' => 'en',
        'format' => 'html',
        ...$payload,
    ]);
}

it('returns a draft translation from the Claude API', function () {
    Http::fake(['api.anthropic.com/*' => Http::response([
        'stop_reason' => 'end_turn',
        'content' => [['type' => 'text', 'text' => "<p>Hello <strong>world</strong></p>\n"]],
    ])]);

    translate()->assertOk()->assertJson(['text' => '<p>Hello <strong>world</strong></p>']);

    Http::assertSent(function (Request $request): bool {
        $body = $request->data();

        return $request->hasHeader('x-api-key', 'test-key')
            && $request->hasHeader('anthropic-version', '2023-06-01')
            && $body['model'] === 'claude-opus-5-5'
            && $body['output_config'] === ['effort' => 'low']
            && str_contains($body['system'], 'from Indonesian to English')
            && str_contains($body['system'], 'The message is HTML')
            && $body['messages'] === [['role' => 'user', 'content' => '<p>Halo <strong>dunia</strong></p>']];
    });
});

it('does not ask for HTML handling on plain text fields', function () {
    Http::fake(['api.anthropic.com/*' => Http::response(['stop_reason' => 'end_turn', 'content' => [['type' => 'text', 'text' => 'Hello']]])]);

    translate(['text' => 'Halo', 'format' => 'text'])->assertOk()->assertJson(['text' => 'Hello']);

    Http::assertSent(fn (Request $request): bool => ! str_contains($request->data()['system'], 'The message is HTML'));
});

it('skips the API for text without anything to translate', function () {
    Http::fake();

    translate(['text' => '<p> </p>'])->assertOk()->assertJson(['text' => '']);

    Http::assertNothingSent();
});

it('reports a missing key without calling the API', function () {
    config(['services.anthropic.key' => null]);
    Http::fake();

    translate()->assertStatus(503)->assertJsonPath('message', 'Terjemahan otomatis belum dikonfigurasi.');

    Http::assertNothingSent();
});

it('explains refusals, truncation and upstream failures', function (array $upstream, string $message) {
    Http::fake(['api.anthropic.com/*' => Http::response(...$upstream)]);

    translate()->assertStatus(503)->assertJsonPath('message', $message);
})->with([
    'refusal' => [[['stop_reason' => 'refusal', 'content' => []]], 'Teks ini tidak dapat diterjemahkan secara otomatis.'],
    'too long' => [[['stop_reason' => 'max_tokens', 'content' => [['type' => 'text', 'text' => 'x']]]], 'Teks terlalu panjang untuk diterjemahkan sekaligus. Terjemahkan per bagian.'],
    'rejected' => [[['error' => ['type' => 'invalid_request_error']], 400], 'Layanan terjemahan menolak permintaan ini.'],
]);

it('validates the languages and format', function (array $payload, string $field) {
    translate($payload)->assertJsonValidationErrors($field);
})->with([
    'same language' => [['to' => 'id'], 'to'],
    'unknown language' => [['to' => 'fr'], 'to'],
    'bad format' => [['format' => 'markdown'], 'format'],
    'empty text' => [['text' => ''], 'text'],
]);

it('is only available to signed-in staff', function () {
    $this->postJson(route('admin.translate'), ['text' => 'x', 'from' => 'id', 'to' => 'en', 'format' => 'text'])
        ->assertUnauthorized();
});

it('tells the admin pages whether translating is available', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get('/admin')->assertInertia(fn ($page) => $page->where('canTranslate', true));

    config(['services.anthropic.key' => null]);

    $this->actingAs($admin)->get('/admin')->assertInertia(fn ($page) => $page->where('canTranslate', false));
});
