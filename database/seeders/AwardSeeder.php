<?php

namespace Database\Seeders;

use App\Enums\AwardType;
use App\Models\Award;
use Illuminate\Database\Seeder;

class AwardSeeder extends Seeder
{
    /**
     * Seed awards and certifications.
     */
    public function run(): void
    {
        $awards = [
            ['event_name' => 'Maroon Day - Universitas Teknologi Digital Indonesia (d/h STMIK AKAKOM Yogyakarta)', 'title' => 'Web Design Competition - 3rd Place (National)', 'type' => AwardType::Competition, 'year' => 2024, 'rank' => 3, 'awarded_at' => '2024-05-25'],
            ['event_name' => 'NIFC - Universitas Muhammadiyah Riau', 'title' => 'Web Design Competition - 5th Place (National)', 'type' => AwardType::Competition, 'year' => 2024, 'rank' => 5, 'awarded_at' => '2024-05-22'],
            ['event_name' => 'Fostifest - Universitas Muhammadiyah Surakarta', 'title' => 'Web Design Competition - 3rd Place (National)', 'type' => AwardType::Competition, 'year' => 2023, 'rank' => 3, 'awarded_at' => '2023-10-29'],
            ['event_name' => 'BSDMP Kominfo Surabaya', 'title' => 'Junior Web Developer Certification - Graduated (National)', 'type' => AwardType::Certification, 'year' => 2023, 'rank' => null, 'awarded_at' => null],
            ['event_name' => 'ByTesFest - Universitas Sebelas Maret', 'title' => 'Web Design Competition - 4th Place (National)', 'type' => AwardType::Competition, 'year' => 2022, 'rank' => 4, 'awarded_at' => null],
            ['event_name' => 'IntechFest - Politeknik Negeri Bali', 'title' => 'Web Design Competition - 1st Place (National)', 'type' => AwardType::Competition, 'year' => 2021, 'rank' => 1, 'awarded_at' => '2021-10-03'],
            ['event_name' => 'Deptics - Universitas PGRI Madiun', 'title' => 'Web Design Competition - 1st Place (National)', 'type' => AwardType::Competition, 'year' => 2021, 'rank' => 1, 'awarded_at' => '2020-03-20'],
            ['event_name' => 'ByTesFest - Universitas Sebelas Maret', 'title' => 'Web Design Competition - 1st Place (National)', 'type' => AwardType::Competition, 'year' => 2020, 'rank' => 1, 'awarded_at' => null],
            ['event_name' => 'LDP 2020 - OSIS SMA Negeri 1 Ponorogo', 'title' => 'Lomba Desain Poster 2020 - 3rd Place (East Java Regional)', 'type' => AwardType::Competition, 'year' => 2020, 'rank' => 3, 'awarded_at' => null],
        ];

        foreach ($awards as $award) {
            Award::query()->create($award);
        }
    }
}
