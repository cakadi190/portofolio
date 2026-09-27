<?php

namespace Database\Seeders;

use App\Models\Technology;
use Illuminate\Database\Seeder;

class TechnologySeeder extends Seeder
{
    /**
     * Seed the technologies referenced by the portfolio items.
     */
    public function run(): void
    {
        $names = [
            'Laravel', 'PHP', 'HTML5', 'CSS3', 'JavaScript', 'jQuery', 'Bootstrap',
            'Kotlin', 'Java', 'NuxtJS', 'VueJS', 'Supabase', 'Vercel',
        ];

        foreach ($names as $name) {
            Technology::query()->firstOrCreate(['name' => $name]);
        }
    }
}
