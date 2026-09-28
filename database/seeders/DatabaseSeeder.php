<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(UserSeeder::class);
        $this->call(CareerSeeder::class);
        $this->call(EducationSeeder::class);
        $this->call(OrganizationSeeder::class);
        $this->call(AwardSeeder::class);
        $this->call(TechnologySeeder::class);
        $this->call(PortfolioCategorySeeder::class);
        $this->call(PortfolioSeeder::class);
        $this->call(PostCategorySeeder::class);
        $this->call(PostSeeder::class);
        $this->call(CoffeePlaceSeeder::class);
    }
}
