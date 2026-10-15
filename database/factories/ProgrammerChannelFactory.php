<?php

namespace Database\Factories;

use App\Enums\ChannelPlatform;
use App\Models\ProgrammerChannel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProgrammerChannel>
 */
class ProgrammerChannelFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'platform' => fake()->randomElement(ChannelPlatform::cases()),
            'description' => fake()->sentence(),
            'url' => fake()->url(),
            'is_featured' => false,
        ];
    }

    public function featured(): static
    {
        return $this->state(['is_featured' => true]);
    }
}
