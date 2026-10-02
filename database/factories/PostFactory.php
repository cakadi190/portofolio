<?php

namespace Database\Factories;

use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->sentence(4),
            'excerpt' => fake()->sentence(),
            'content' => '<p>'.fake()->paragraph().'</p>',
            'is_published' => true,
            'published_at' => now(),
        ];
    }
}
