<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    /**
     * Seed the organization history.
     */
    public function run(): void
    {
        $organizations = [
            ['name' => 'UKM Polytehnic Association of Language Study (PALS)', 'description' => 'Wakil Ketua 2', 'start_date' => '2024-01-01', 'end_date' => '2025-12-31'],
            ['name' => 'UKM Polytehnic Association of Language Study (PALS)', 'description' => 'Divisi Kominfo', 'start_date' => '2023-01-01', 'end_date' => '2024-12-31'],
            ['name' => 'Dewan Kerja Daerah Jawa Timur', 'description' => 'Panitia Sangga Kerja Divisi Humas dan Publikasi', 'start_date' => '2022-01-01', 'end_date' => '2023-12-31'],
            ['name' => 'Humas Dewan Kerja Nasional', 'description' => 'Divisi Website', 'start_date' => '2022-01-01', 'end_date' => '2023-12-31'],
            ['name' => 'Winscout SMASA Ngawi', 'description' => 'Bidang Bimbingan Dan Pengembangan (HUMAS dan Publikasi)', 'start_date' => '2021-01-01', 'end_date' => '2022-12-31'],
            ['name' => 'Artesis SMASA NGAWI', 'description' => 'Bidang Humas dan Teknologi Informasi dan Publikasi', 'start_date' => '2021-01-01', 'end_date' => '2022-12-31'],
            ['name' => 'Dewan Penggalang SMP Negeri 1 Padas', 'description' => 'Divisi Perlengkapan dan Persiapan Kegiatan', 'start_date' => '2016-01-01', 'end_date' => '2017-12-31'],
        ];

        foreach ($organizations as $organization) {
            Organization::query()->create($organization);
        }
    }
}
