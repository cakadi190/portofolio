<?php

namespace Database\Seeders;

use App\Models\PostCategory;
use Illuminate\Database\Seeder;

class PostCategorySeeder extends Seeder
{
    /**
     * Seed the blog post categories.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Teknologi', 'color' => '#0ea5e9'],
            ['name' => 'Tips & Trik', 'color' => '#22c55e'],
            ['name' => 'Pengalaman', 'color' => '#f97316'],
        ];

        foreach ($categories as $category) {
            PostCategory::query()->firstOrCreate(['name' => $category['name']], $category);
        }
    }
}
