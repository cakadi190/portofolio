<?php

namespace Database\Seeders;

use App\Enums\SocialiteProvider;
use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    /**
     * Seed placeholder Socialite rows so every provider is administrator
     * configurable from the System Settings page. Left disabled until an
     * administrator fills in real credentials.
     */
    public function run(): void
    {
        foreach (SocialiteProvider::cases() as $provider) {
            SystemSetting::set(SystemSetting::socialiteKey($provider, 'client_id'), null);
            SystemSetting::set(SystemSetting::socialiteKey($provider, 'client_secret'), null);
            SystemSetting::set(SystemSetting::socialiteKey($provider, 'is_active'), false);
        }
    }
}
