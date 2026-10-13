<?php

namespace Database\Factories;

use App\Models\Note;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Note>
 */
class NoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->sentence(3),
            'excerpt' => fake()->sentence(),
            'content' => '<p>'.fake()->paragraph().'</p>',
            'is_published' => true,
            'published_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(['is_published' => false, 'published_at' => null]);
    }
}
