<?php

namespace Database\Seeders;

use App\Models\DevCommunity;
use Illuminate\Database\Seeder;

class DevCommunitySeeder extends Seeder
{
    /**
     * Seed a few starter entries; the rest is managed from the admin area.
     */
    public function run(): void
    {
        $items = [
            ['name' => 'AlgoHub Indonesia', 'platform' => 'discord', 'description' => 'Direktori komunitas pemrograman, pengembangan web, rekayasa perangkat lunak, dan TI di Indonesia.', 'invite_url' => 'https://discord.gg/Mj5FbCYea', 'is_featured' => true],
            ['name' => 'Otodidak Ngoding', 'platform' => 'telegram', 'description' => 'Tutorial daring untuk belajar pemrograman secara otodidak: C/C++ untuk IoT, Java, Kotlin, JavaScript, Go, dan Python.', 'invite_url' => 'https://t.me/otodidak_ngoding', 'is_featured' => false],
        ];

        foreach ($items as $item) {
            DevCommunity::query()->firstOrCreate(['invite_url' => $item['invite_url']], $item);
        }
    }
}
