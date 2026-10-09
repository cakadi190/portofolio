<?php

use App\Models\SpeakingEngagement;

test('the speaking page splits upcoming engagements from the archive', function () {
    SpeakingEngagement::factory()->upcoming()->create(['title' => 'Acara Depan']);
    SpeakingEngagement::factory()->create(['title' => 'Acara Lalu']);

    $this->get(route('speaking.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('speaking/index')
            ->has('upcoming', 1)
            ->where('upcoming.0.title', 'Acara Depan')
            ->has('past', 1)
            ->where('past.0.title', 'Acara Lalu'));
});

test('unpublished engagements are hidden from the public page', function () {
    SpeakingEngagement::factory()->unpublished()->create();

    $this->get(route('speaking.index'))
        ->assertInertia(fn ($page) => $page->has('upcoming', 0)->has('past', 0));
});

test('upcoming engagements are listed soonest first', function () {
    SpeakingEngagement::factory()->create(['title' => 'Nanti', 'starts_at' => now()->addMonths(2)]);
    SpeakingEngagement::factory()->create(['title' => 'Segera', 'starts_at' => now()->addWeek()]);

    $this->get(route('speaking.index'))
        ->assertInertia(fn ($page) => $page->where('upcoming.0.title', 'Segera')->where('upcoming.1.title', 'Nanti'));
});

test('the speaking page is indexable and in the pages sitemap', function () {
    $this->get(route('speaking.index'))->assertDontSee('noindex');
    $this->get(route('sitemaps.pages'))->assertSee(route('speaking.index'), false);
});
