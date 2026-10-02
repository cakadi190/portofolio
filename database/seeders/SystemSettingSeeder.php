<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    /**
     * Seed the contact details and mail settings managed from the admin panel.
     */
    public function run(): void
    {
        $settings = [
            'contact_address' => 'Ngawi, Jawa Timur',
            'contact_timezone' => 'GMT+07:00 (Indonesian Western Time / UTC+7)',
            'contact_email' => 'cakadi190@gmail.com',
            'contact_whatsapp' => '081234771365',
            'contact_website' => 'https://www.cakadi.web.id',
            'social_facebook' => 'https://www.facebook.com/cakadi190',
            'social_instagram' => 'https://www.instagram.com/masadi.dev/',
            'social_twitter' => 'https://x.com/cakadi190',
            'social_youtube' => 'https://youtube.com/@catatancakadi',
            'social_linkedin' => 'https://linkedin.com/in/cakadi190',
            'social_tiktok' => 'https://tiktok.com/@cakadi190',
            SystemSetting::CONTACT_RECIPIENT_EMAIL => 'cakadi190@gmail.com',
        ];

        foreach ($settings as $key => $value) {
            SystemSetting::query()->updateOrCreate(['key' => $key], ['value' => $value, 'is_active' => true]);
        }
    }
}
