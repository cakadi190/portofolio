<?php

use App\Models\Portfolio;
use App\Models\PortfolioRating;

function validReview(array $overrides = []): array
{
    return array_merge([
        'reviewer_name' => 'Budi',
        'reviewer_email' => 'budi@example.com',
        'title' => 'Kerja bagus',
        'rating' => 5,
        'comment' => '<p>Mantap <strong>sekali</strong></p>',
    ], $overrides);
}

test('a visitor can submit a review that awaits approval', function () {
    $portfolio = Portfolio::factory()->create();

    $this->post(route('portfolios.reviews.store', $portfolio), validReview())
        ->assertRedirect(route('portfolios.show', $portfolio));

    $review = PortfolioRating::query()->firstOrFail();

    expect($review->is_approved)->toBeFalse();
    expect($review->portfolio_id)->toBe($portfolio->id);
});

test('required fields are validated', function () {
    $portfolio = Portfolio::factory()->create();

    $this->post(route('portfolios.reviews.store', $portfolio), ['comment' => '<p></p>'])
        ->assertSessionHasErrors(['reviewer_name', 'reviewer_email', 'title', 'rating', 'comment']);

    expect(PortfolioRating::query()->count())->toBe(0);
});

test('unsafe html is stripped from the comment', function () {
    $portfolio = Portfolio::factory()->create();

    $this->post(route('portfolios.reviews.store', $portfolio), validReview([
        'comment' => '<p onclick="x()">Hai</p><script>alert(1)</script><img src=x onerror=alert(1)>',
    ]));

    expect(PortfolioRating::query()->firstOrFail()->comment)->toBe('<p>Hai</p>alert(1)');
});

test('only approved reviews are shown on the portfolio page', function () {
    $portfolio = Portfolio::factory()->create();
    PortfolioRating::factory()->for($portfolio)->create(['title' => 'Tampil', 'rating' => 4]);
    PortfolioRating::factory()->for($portfolio)->create(['title' => 'Tersembunyi', 'is_approved' => false]);

    $this->get(route('portfolios.show', $portfolio))
        ->assertInertia(fn ($page) => $page
            ->component('portfolio/show')
            ->has('reviews', 1)
            ->where('reviews.0.title', 'Tampil')
            ->where('reviewSummary.count', 1));
});
