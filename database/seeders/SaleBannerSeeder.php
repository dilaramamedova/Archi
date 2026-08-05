<?php

namespace Database\Seeders;

use App\Models\SaleBanner;
use Illuminate\Database\Seeder;

class SaleBannerSeeder extends Seeder
{
    public function run(): void
    {
        SaleBanner::updateOrCreate(
            ['sort_order' => 1],
            [
                'title' => [
                    'az' => 'SALE • OUTLET •',
                    'ru' => 'SALE • OUTLET •',
                    'en' => 'SALE • OUTLET •',
                ],
                'is_active' => true,
            ],
        );
    }
}
