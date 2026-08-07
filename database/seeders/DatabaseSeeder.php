<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@archi.test'],
            [
                'name' => 'ARCHI Admin',
                'first_name' => 'ARCHI',
                'last_name' => 'Admin',
                'password' => 'password',
                'role' => UserRole::Admin,
                'status' => UserStatus::Active,
            ],
        );

        User::where('email', 'admin@archi.test')->update([
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);

        $this->call([
            TranslationsSeeder::class,
            SettingSeeder::class,
            MenuItemSeeder::class,
            SocialLinkSeeder::class,
            AboutSeeder::class,
            LegalPageSeeder::class,
            DemoSeeder::class,
        ]);
    }
}
