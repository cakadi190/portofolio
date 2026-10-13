<?php

namespace Database\Factories;

use App\Enums\BlogCommentStatus;
use App\Models\GuestbookEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GuestbookEntry>
 */
class GuestbookEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'parent_id' => null,
            'body' => fake()->sentence(),
            'status' => BlogCommentStatus::Approved,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => BlogCommentStatus::Pending]);
    }

    public function replyTo(GuestbookEntry $parent): static
    {
        return $this->state(['parent_id' => $parent->id]);
    }
}
