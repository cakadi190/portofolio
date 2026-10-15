<?php

namespace Database\Factories;

use App\Enums\CommunityPlatform;
use App\Models\DevCommunity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DevCommunity>
 */
class DevCommunityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'platform' => fake()->randomElement(CommunityPlatform::cases()),
            'description' => fake()->sentence(),
            'invite_url' => fake()->url(),
            'member_count' => fake()->numberBetween(10, 5000),
            'is_featured' => false,
        ];
    }

    public function featured(): static
    {
        return $this->state(['is_featured' => true]);
    }
}
