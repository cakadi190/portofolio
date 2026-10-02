<?php

namespace Database\Factories;

use App\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'disk' => 'public',
            'path' => 'media/'.fake()->unique()->uuid().'.webp',
            'name' => fake()->word().'.webp',
            'alt' => null,
            'mime_type' => 'image/webp',
            'size' => fake()->numberBetween(10_000, 300_000),
            'width' => 800,
            'height' => 600,
        ];
    }

    public function pdf(): static
    {
        return $this->state(fn (): array => [
            'path' => 'media/'.fake()->unique()->uuid().'.pdf',
            'name' => fake()->word().'.pdf',
            'mime_type' => 'application/pdf',
            'width' => null,
            'height' => null,
        ]);
    }
}
