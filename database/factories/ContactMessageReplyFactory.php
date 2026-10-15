<?php

namespace Database\Factories;

use App\Enums\ContactReplyStatus;
use App\Models\ContactMessage;
use App\Models\ContactMessageReply;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactMessageReply>
 */
class ContactMessageReplyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contact_message_id' => ContactMessage::factory(),
            'user_id' => null,
            'subject' => 'Membalas: '.fake()->sentence(3),
            'body' => '<p>'.fake()->paragraph().'</p>',
            'attachments' => null,
            'status' => ContactReplyStatus::Queued,
        ];
    }

    public function sent(): static
    {
        return $this->state(fn (): array => ['status' => ContactReplyStatus::Sent, 'sent_at' => now()]);
    }
}
