<?php

namespace Database\Factories;

use App\Enums\SpeakingFormat;
use App\Enums\SpeakingRole;
use App\Models\SpeakingEngagement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SpeakingEngagement>
 */
class SpeakingEngagementFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'organizer' => fake()->company(),
            'role' => SpeakingRole::Speaker,
            'format' => SpeakingFormat::Offline,
            'location' => fake()->city(),
            'starts_at' => fake()->dateTimeBetween('-2 years', '-1 week'),
            'ends_at' => null,
            'registration_url' => null,
            'description' => null,
            'poster' => null,
            'is_published' => true,
        ];
    }

    public function upcoming(): static
    {
        return $this->state(fn (): array => [
            'starts_at' => fake()->dateTimeBetween('+1 week', '+3 months'),
        ]);
    }

    public function unpublished(): static
    {
        return $this->state(fn (): array => ['is_published' => false]);
    }
}
