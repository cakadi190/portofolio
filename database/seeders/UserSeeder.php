<?php

namespace Database\Seeders;

use App\Enums\Gender;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'me@cakadi.web.id'],
            [
                'name' => 'Cak Adi',
                'password' => 'NanaYourBusiness2026!',
                'account_type' => UserRole::Admin,
                'phone' => '085747028054',
                'gender' => Gender::Male,
                'email_verified_at' => now(),
            ]
        );
    }
}
