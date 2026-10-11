<?php

use App\Models\Clarification;
use App\Models\Portfolio;
use App\Models\Post;
use App\Models\Service;

const XSS_PAYLOAD = '<p>aman</p><script>alert(1)</script><img src="/x.webp" onerror="alert(1)"><a href="javascript:alert(1)">klik</a>';

function expectNoScriptIn(string $html): void
{
    expect($html)->toContain('aman')
        ->not->toContain('<script>alert')
        ->not->toContain('onerror')
        ->not->toContain('javascript:');
}

test('post content is sanitized before it reaches the page', function () {
    $post = Post::factory()->create(['content' => XSS_PAYLOAD]);

    $this->get(route('blog.show', $post))
        ->assertInertia(fn ($page) => $page->where('post.content', fn ($content) => expectNoScriptIn($content) ?? true));
});

test('clarification content is sanitized before it reaches the page', function () {
    $clarification = Clarification::factory()->create(['content' => XSS_PAYLOAD]);

    $this->get(route('clarifications.show', $clarification))
        ->assertInertia(fn ($page) => $page->where('clarification.content', fn ($content) => expectNoScriptIn($content) ?? true));
});

test('portfolio and service descriptions are sanitized before they reach the page', function () {
    $portfolio = Portfolio::factory()->create(['description' => XSS_PAYLOAD]);
    $service = Service::factory()->create(['description' => XSS_PAYLOAD]);

    $this->get(route('portfolios.show', $portfolio))
        ->assertInertia(fn ($page) => $page->where('portfolio.description', fn ($description) => expectNoScriptIn($description) ?? true));

    $this->get(route('services.show', $service))
        ->assertInertia(fn ($page) => $page->where('service.description', fn ($description) => expectNoScriptIn($description) ?? true));
});
