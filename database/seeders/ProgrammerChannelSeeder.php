<?php

namespace Database\Seeders;

use App\Models\ProgrammerChannel;
use Illuminate\Database\Seeder;

class ProgrammerChannelSeeder extends Seeder
{
    /**
     * Seed a few starter entries; the rest is managed from the admin area.
     */
    public function run(): void
    {
        $items = [
            ['name' => 'Programmer Zaman Now', 'platform' => 'youtube', 'description' => 'Tutorial pemrograman berbahasa Indonesia: Java, PHP, Go, dan banyak lagi.', 'url' => 'https://www.youtube.com/@ProgrammerZamanNow', 'is_featured' => true],
            ['name' => 'Web Programming UNPAS', 'platform' => 'youtube', 'description' => 'Belajar pemrograman web dari dasar bersama Sandhika Galih.', 'url' => 'https://www.youtube.com/@sandhikagalih', 'is_featured' => false],
        ];

        foreach ($items as $item) {
            ProgrammerChannel::query()->firstOrCreate(['url' => $item['url']], $item);
        }
    }
}
