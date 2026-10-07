<?php

namespace Database\Factories;

use App\Enums\BlogCommentStatus;
use App\Models\BlogComment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BlogComment>
 */
class BlogCommentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
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

    public function rejected(): static
    {
        return $this->state(['status' => BlogCommentStatus::Rejected]);
    }

    /**
     * Reply to the given comment, on the same post.
     */
    public function replyTo(BlogComment $parent): static
    {
        return $this->state([
            'post_id' => $parent->post_id,
            'parent_id' => $parent->id,
        ]);
    }
}
