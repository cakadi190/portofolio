<?php

use App\Models\Award;
use App\Models\Career;
use App\Models\Portfolio;

test('the homepage count cards are derived from the CV data', function () {
    Career::query()->create([
        'position' => 'Developer',
        'company' => 'Acme',
        'location' => 'Madiun',
        'start_date' => now()->subYears(5)->subMonth()->toDateString(),
    ]);
    Portfolio::factory()->count(2)->create();
    Portfolio::factory()->create(['is_private' => true]);
    Award::factory()->count(3)->create();

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('app/index')
            ->has('stats', 4)
            ->where('stats.0.value', 5)
            ->where('stats.1.value', 2)
            ->where('stats.2.value', 3)
            ->where('stats.3.value', 0));
});

test('the career counter is zero without any career entry', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('stats.0.value', 0));
});
