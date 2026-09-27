<?php

namespace Database\Seeders;

use App\Models\Career;
use Illuminate\Database\Seeder;

class CareerSeeder extends Seeder
{
    /**
     * Seed the career history.
     */
    public function run(): void
    {
        $careers = [
            ['position' => 'Kepala Bidang Teknologi (Chief Technology Officer)', 'company' => 'PT Hello Consulting Indonesia', 'location' => 'Kebayoran Lama, Kota Adm. Jakarta Selatan, DKI Jakarta', 'start_date' => '2026-01-12', 'end_date' => null],
            ['position' => 'Pemilik Bisnis', 'company' => 'PT Kodingin Digital Nusantara', 'location' => 'Ngawi, Jawa Timur', 'start_date' => '2024-10-10', 'end_date' => null],
            ['position' => 'Guru Ekskul', 'company' => 'SMK Negeri Takeran', 'location' => 'Takeran, Kab. Magetan, Jawa Timur', 'start_date' => '2025-08-01', 'end_date' => '2026-02-28'],
            ['position' => 'Magang Fullstack Web Developer', 'company' => 'PT Humma Teknologi Indonesia', 'location' => 'Karangploso, Kab. Malang, Jawa Timur', 'start_date' => '2024-02-05', 'end_date' => '2024-07-31'],
            ['position' => 'Chief Technology Officer & Co-Founder', 'company' => 'PT Buat Usaha Digital Indonesia', 'location' => 'Slawi, Kab. Tegal, Jawa Tengah', 'start_date' => '2023-07-01', 'end_date' => '2023-12-31'],
            ['position' => 'Junior Fullstack Web Developer', 'company' => 'Asean Fintech Group Ltd.', 'location' => 'Semarang Tengah, Kota Semarang, Jawa Tengah', 'start_date' => '2021-11-01', 'end_date' => '2022-02-28'],
            ['position' => 'Pemilik Bisnis', 'company' => 'Ahsana Digital Intermedia (Dulu Dikenal Sebagai Dasa Kreativa Studio)', 'location' => 'Taman, Kota Madiun, Jawa Timur', 'start_date' => '2021-02-01', 'end_date' => '2024-10-31'],
            ['position' => 'Frontend Web Developer', 'company' => 'CV Dokternet Indonesia', 'location' => 'Waru, Sidoarjo, Jawa Timur', 'start_date' => '2021-10-01', 'end_date' => '2021-11-30'],
            ['position' => 'Desainer Grafis', 'company' => 'Artografi Indonesia', 'location' => 'Bayemtaman, Magetan, Jawa Timur', 'start_date' => '2020-11-01', 'end_date' => '2021-01-31'],
        ];

        foreach ($careers as $career) {
            Career::query()->create($career);
        }
    }
}
