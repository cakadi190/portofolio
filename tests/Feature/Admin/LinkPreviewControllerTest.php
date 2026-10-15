<?php

use App\Models\User;
use App\Services\LinkPreviewService;

test('guests cannot request a link preview', function () {
    $this->postJson(route('admin.link-preview'), ['url' => 'https://example.com'])->assertUnauthorized();
});

test('a preview is returned for an editor', function () {
    $this->mock(LinkPreviewService::class)
        ->shouldReceive('preview')
        ->with('https://example.com/post')
        ->andReturn(['url' => 'https://example.com/post', 'title' => 'Hello', 'image' => '', 'siteName' => 'example.com']);

    $this->actingAs(User::factory()->admin()->create())
        ->postJson(route('admin.link-preview'), ['url' => 'https://example.com/post'])
        ->assertOk()
        ->assertJsonPath('title', 'Hello');
});

test('an unreachable page responds with a validation-style error', function () {
    $this->mock(LinkPreviewService::class)->shouldReceive('preview')->andReturnNull();

    $this->actingAs(User::factory()->admin()->create())
        ->postJson(route('admin.link-preview'), ['url' => 'https://example.com'])
        ->assertStatus(422);
});

test('non-http urls are rejected', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->postJson(route('admin.link-preview'), ['url' => 'file:///etc/passwd'])
        ->assertJsonValidationErrors('url');
});

test('private network addresses are never fetched', function (string $url) {
    expect(app(LinkPreviewService::class)->preview($url))->toBeNull();
})->with(['http://127.0.0.1/', 'http://169.254.169.254/latest/meta-data', 'http://10.0.0.5/', 'http://localhost/', 'https://example.com:8080/']);
