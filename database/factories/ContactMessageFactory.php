<?php

namespace Database\Factories;

use App\Enums\ContactReason;
use App\Models\ContactMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactMessage>
 */
class ContactMessageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'reason' => ContactReason::General,
            'message' => '<p>'.fake()->paragraph().'</p>',
            'read_at' => null,
        ];
    }
}
