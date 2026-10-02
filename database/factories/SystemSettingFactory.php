<?php

namespace Database\Factories;

use App\Models\SystemSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SystemSetting>
 */
class SystemSettingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => str_replace('-', '_', fake()->unique()->slug(3)),
            'value' => fake()->sentence(3),
            'is_active' => true,
        ];
    }

    public function contactRecipient(string $email = 'inbox@example.com'): static
    {
        return $this->state(fn (): array => [
            'key' => SystemSetting::CONTACT_RECIPIENT_EMAIL,
            'value' => $email,
        ]);
    }
}
