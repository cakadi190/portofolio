<?php

namespace Database\Factories;

use App\Enums\ClarificationVerdict;
use App\Models\Clarification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Clarification>
 */
class ClarificationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(6),
            'claim' => fake()->sentence(12),
            'claim_origin' => 'Unggahan media sosial, '.fake()->date('d F Y'),
            'verdict' => ClarificationVerdict::False,
            'summary' => fake()->sentence(10),
            'content' => '<p>'.fake()->paragraph().'</p>',
            'apology' => null,
            'cover_image' => null,
            'sources' => [
                ['title' => fake()->sentence(3), 'url' => fake()->url()],
            ],
            'updates' => [],
            'is_published' => true,
            'published_at' => fake()->dateTimeBetween('-1 year', '-1 day'),
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn (): array => ['is_published' => false]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (): array => ['published_at' => fake()->dateTimeBetween('+1 day', '+1 month')]);
    }

    public function withApology(): static
    {
        return $this->state(fn (): array => [
            'verdict' => ClarificationVerdict::TrueWithApology,
            'apology' => 'Saya meminta maaf atas kekeliruan tersebut.',
        ]);
    }
}
