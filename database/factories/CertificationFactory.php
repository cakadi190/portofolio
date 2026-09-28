<?php

namespace Database\Factories;

use App\Models\Certification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Certification>
 */
class CertificationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'issuer' => fake()->company(),
            'issued_at' => fake()->date(),
            'expires_at' => null,
            'credential_id' => fake()->bothify('??-########'),
            'credential_url' => null,
            'file' => 'certifications/'.fake()->uuid().'.pdf',
        ];
    }
}
