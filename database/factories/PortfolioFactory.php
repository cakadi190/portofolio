<?php

namespace Database\Factories;

use App\Models\Portfolio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Portfolio>
 */
class PortfolioFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'image' => 'portfolios/placeholder.jpg',
            'short_desc' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'demo_link' => fake()->url(),
            'source_code' => fake()->url(),
            'is_private' => false,
        ];
    }
}
