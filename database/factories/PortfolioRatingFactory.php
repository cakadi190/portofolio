<?php

namespace Database\Factories;

use App\Models\Portfolio;
use App\Models\PortfolioRating;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PortfolioRating>
 */
class PortfolioRatingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'portfolio_id' => Portfolio::factory(),
            'reviewer_name' => fake()->name(),
            'reviewer_email' => fake()->safeEmail(),
            'reviewer_company' => fake()->company(),
            'title' => fake()->sentence(4),
            'rating' => fake()->numberBetween(1, 5),
            'comment' => '<p>'.fake()->paragraph().'</p>',
            'is_approved' => true,
        ];
    }
}
