<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Seed the services.
     */
    public function run(): void
    {
        $services = [
            ['slug' => 'website', 'name' => 'Website', 'color' => '#0ea5e9', 'description' => '<p>Pembuatan website company profile, landing page, sistem informasi, hingga aplikasi web fullstack yang cepat, rapi, dan mudah dikelola.</p>'],
            ['slug' => 'mobile', 'name' => 'Mobile', 'color' => '#22c55e', 'description' => '<p>Pengembangan aplikasi mobile untuk kebutuhan bisnis maupun komunitas dengan antarmuka yang nyaman digunakan.</p>'],
            ['slug' => 'design-ui-ux', 'name' => 'Design UI/UX', 'color' => '#a855f7', 'description' => '<p>Perancangan antarmuka dan pengalaman pengguna, dari wireframe hingga prototipe siap dikembangkan.</p>'],
            ['slug' => 'desain-grafis', 'name' => 'Desain Grafis', 'color' => '#f97316', 'description' => '<p>Desain visual seperti logo, poster, dan materi promosi untuk memperkuat identitas brand Anda.</p>'],
        ];

        foreach ($services as $service) {
            Service::query()->firstOrCreate(['slug' => $service['slug']], $service);
        }
    }
}
