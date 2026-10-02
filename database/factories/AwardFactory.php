<?php

namespace Database\Factories;

use App\Enums\AwardType;
use App\Models\Award;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Award>
 */
class AwardFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_name' => fake()->company(),
            'title' => fake()->sentence(3),
            'type' => AwardType::Competition,
            'year' => fake()->numberBetween(2015, (int) date('Y')),
            'rank' => fake()->numberBetween(1, 3),
            'awarded_at' => fake()->date(),
        ];
    }
}
