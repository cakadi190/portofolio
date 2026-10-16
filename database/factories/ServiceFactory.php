<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'color' => fake()->hexColor(),
            'image' => 'services/placeholder.jpg',
            'description' => '<p>'.fake()->paragraph().'</p>',
        ];
    }

    /**
     * The text also exists in English.
     */
    public function withEnglish(string $name = 'English name'): static
    {
        return $this->state(fn (): array => [
            'name_en' => $name,
            'description_en' => '<p>English body</p>',
        ]);
    }
}
