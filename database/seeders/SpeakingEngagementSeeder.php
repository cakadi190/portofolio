<?php

namespace Database\Seeders;

use App\Enums\SpeakingFormat;
use App\Enums\SpeakingRole;
use App\Models\SpeakingEngagement;
use App\Models\User;
use App\Services\MediaService;
use Illuminate\Database\Seeder;

class SpeakingEngagementSeeder extends Seeder
{
    /**
     * Seed speaking engagements with their posters from public/images/speaking.
     */
    public function run(): void
    {
        $media = app(MediaService::class);
        $userId = User::query()->value('id');

        $engagements = [
            [
                'title' => 'Building Digital Solutions in the AI Era: Mengubah Masalah Nyata Menjadi Solusi Digital dengan Artificial Intelligence',
                'organizer' => 'Diwostech.id',
                'role' => SpeakingRole::Trainer,
                'format' => SpeakingFormat::Online,
                'location' => 'Zoom & YouTube',
                'starts_at' => '2026-10-11 19:30',
                'ends_at' => '2026-10-11 21:30',
                'registration_url' => 'https://bit.ly/Diwostech8',
                'description' => 'Pelatihan hands-on terbuka untuk umum bersama Claude dan Claude Code. Gratis, dengan e-sertifikat 2 JP nasional, e-book, konsultasi gratis, dan rekaman pelatihan.',
                'poster' => 'ai-era-diwostech.png',
            ],
            [
                'title' => 'Menumbuhkan Jiwa Wirausaha Muda dengan Kreativitas dan Inovasi: An Entrepreneur\'s Story & eXPERIENCE',
                'organizer' => 'Taska Expo & Seminar - UPI Kampus Serang',
                'role' => SpeakingRole::Speaker,
                'format' => SpeakingFormat::Hybrid,
                'location' => 'Gd. Baru UPI Serang',
                'starts_at' => '2024-06-08 09:00',
                'ends_at' => null,
                'registration_url' => null,
                'description' => 'Sesi seminar kewirausahaan sebagai pemilik software house dan jasa desain web Ahsana Digital Intermedia (d/h Dasa Kreativa Studio).',
                'poster' => 'taska-expo-upi.jpg',
            ],
        ];

        foreach ($engagements as $engagement) {
            $engagement['poster'] = $media->storeFromPublicPath("/images/speaking/{$engagement['poster']}", $userId)->path;

            SpeakingEngagement::query()->create($engagement);
        }
    }
}
