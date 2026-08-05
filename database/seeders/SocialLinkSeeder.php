<?php

namespace Database\Seeders;

use App\Models\SocialLink;
use Illuminate\Database\Seeder;

class SocialLinkSeeder extends Seeder
{
    public function run(): void
    {
        SocialLink::create([
            'platform' => 'instagram',
            'url' => 'https://instagram.com/archi.az',
            'icon' => 'icon-instagram-white.svg',
            'sort_order' => 1,
        ]);
    }
}
