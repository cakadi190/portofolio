<?php

use App\Models\Clarification;

test('the index lists only published, due clarifications newest first', function () {
    Clarification::factory()->create(['title' => 'Lama', 'published_at' => now()->subWeek()]);
    Clarification::factory()->create(['title' => 'Baru', 'published_at' => now()->subDay()]);
    Clarification::factory()->unpublished()->create();
    Clarification::factory()->scheduled()->create();

    $this->get(route('clarifications.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('clarifications/index')
            ->has('clarifications.data', 2)
            ->where('clarifications.data.0.title', 'Baru')
            ->where('clarifications.data.1.title', 'Lama')
            ->has('verdicts', 5));
});

test('the index can be filtered by verdict and ignores unknown verdicts', function () {
    Clarification::factory()->create(['verdict' => 'false']);
    Clarification::factory()->withApology()->create();

    $this->get(route('clarifications.index', ['verdict' => 'true_with_apology']))
        ->assertInertia(fn ($page) => $page
            ->has('clarifications.data', 1)
            ->where('clarifications.data.0.verdict', 'true_with_apology')
            ->where('activeVerdict', 'true_with_apology'));

    $this->get(route('clarifications.index', ['verdict' => 'wizard']))
        ->assertInertia(fn ($page) => $page->has('clarifications.data', 2)->where('activeVerdict', null));
});

test('a published clarification shows its claim, sources, apology and newest update first', function () {
    $clarification = Clarification::factory()->withApology()->create([
        'sources' => [['title' => 'Pernyataan resmi', 'url' => 'https://example.com/a']],
        'updates' => [
            ['date' => '2026-01-01', 'note' => 'Terbit pertama.'],
            ['date' => '2026-02-01', 'note' => 'Bukti ditambahkan.'],
        ],
    ]);

    $this->get(route('clarifications.show', $clarification))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('clarifications/show')
            ->where('clarification.claim', $clarification->claim)
            ->where('clarification.verdictLabel', 'Benar, Saya Meminta Maaf')
            ->where('clarification.apology', 'Saya meminta maaf atas kekeliruan tersebut.')
            ->where('clarification.sources.0.url', 'https://example.com/a')
            ->where('clarification.updates.0.note', 'Bukti ditambahkan.'));
});

test('drafts and scheduled clarifications are not found', function () {
    $this->get(route('clarifications.show', Clarification::factory()->unpublished()->create()))->assertNotFound();
    $this->get(route('clarifications.show', Clarification::factory()->scheduled()->create()))->assertNotFound();
});

test('the detail page describes itself as an article for search engines', function () {
    $clarification = Clarification::factory()->create(['title' => 'Klarifikasi Terukur']);

    $this->get(route('clarifications.show', $clarification))
        ->assertSee('Klarifikasi Terukur', false)
        ->assertSee('BlogPosting', false);
});

test('the index page is indexable and listed in the pages sitemap', function () {
    $this->get(route('clarifications.index'))->assertDontSee('noindex');
    $this->get(route('sitemaps.pages'))->assertSee(route('clarifications.index'), false);
});

test('the clarifications sitemap only lists published, due clarifications', function () {
    $published = Clarification::factory()->create(['cover_image' => 'media/cover.webp']);
    $draft = Clarification::factory()->unpublished()->create();
    $scheduled = Clarification::factory()->scheduled()->create();

    $response = $this->get(route('sitemaps.clarifications'))->assertOk();

    expect(simplexml_load_string($response->getContent()))->not->toBeFalse();
    $response
        ->assertSee(route('clarifications.show', $published), escape: false)
        ->assertSee(url('/storage/media/cover.webp'), escape: false)
        ->assertDontSee(route('clarifications.show', $draft), escape: false)
        ->assertDontSee(route('clarifications.show', $scheduled), escape: false);
});
