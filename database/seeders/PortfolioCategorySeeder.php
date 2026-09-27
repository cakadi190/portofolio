<?php

namespace Database\Seeders;

use App\Models\PortfolioCategory;
use Illuminate\Database\Seeder;

class PortfolioCategorySeeder extends Seeder
{
    /**
     * Seed the portfolio categories.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Website', 'color' => '#0ea5e9'],
            ['name' => 'Mobile', 'color' => '#22c55e'],
            ['name' => 'Design UI/UX', 'color' => '#a855f7'],
            ['name' => 'Desain Grafis', 'color' => '#f97316'],
        ];

        foreach ($categories as $category) {
            PortfolioCategory::query()->firstOrCreate(['name' => $category['name']], $category);
        }
    }
}
