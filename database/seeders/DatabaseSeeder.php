<?php

namespace Database\Seeders;

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
                'role' => 'buyer',
                'status' => UserStatus::Active,
            ],
        );

        $this->call([
            SettingSeeder::class,
            MenuItemSeeder::class,
            SocialLinkSeeder::class,
            AboutSeeder::class,
            DemoSeeder::class,
        ]);
    }
}
