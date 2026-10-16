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

    /**
     * The post also exists in English (the Indonesian text stays as generated).
     */
    public function withEnglish(string $title = 'English title', ?string $indonesianTitle = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'title' => ['id' => $indonesianTitle ?? $attributes['title'], 'en' => $title],
            'excerpt' => ['id' => $attributes['excerpt'], 'en' => 'English excerpt'],
            'content' => ['id' => $attributes['content'], 'en' => '<p>English body</p>'],
        ]);
    }
}
