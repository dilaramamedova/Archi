<?php

namespace Database\Seeders;

use App\Models\ServiceIcon;
use Illuminate\Database\Seeder;

class ServiceIconSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'title'      => ['az' => 'Xüsusi sertifikatlı ustalar', 'ru' => 'Сертифицированные мастера', 'en' => 'Certified pros'],
                'text'       => ['az' => 'reytinq və rəylərlə', 'ru' => 'с рейтингом и отзывами', 'en' => 'with ratings & reviews'],
                'icon'       => 'icon-user-grey.svg',
                'sort_order' => 1,
            ],
            [
                'title'      => ['az' => 'Sürətli çatdırılma', 'ru' => 'Быстрая доставка', 'en' => 'Fast delivery'],
                'text'       => ['az' => 'Bütün regionlara', 'ru' => 'Во все регионы', 'en' => 'To every region'],
                'icon'       => 'icon-truck-grey.svg',
                'sort_order' => 2,
            ],
            [
                'title'      => ['az' => 'Təhlükəsiz ödəniş', 'ru' => 'Безопасная оплата', 'en' => 'Secure payment'],
                'text'       => ['az' => '3D Secure', 'ru' => '3D Secure', 'en' => '3D Secure'],
                'icon'       => 'icon-shield-grey.svg',
                'sort_order' => 3,
            ],
            [
                'title'      => ['az' => 'Pulsuz konsultasiya', 'ru' => 'Бесплатная консультация', 'en' => 'Free consultation'],
                'text'       => ['az' => 'Mütəxəssislər tərəfindən', 'ru' => 'от специалистов', 'en' => 'by specialists'],
                'icon'       => 'icon-chat-grey.svg',
                'sort_order' => 4,
            ],
        ];

        foreach ($items as $item) {
            ServiceIcon::updateOrCreate(
                ['sort_order' => $item['sort_order']],
                $item,
            );
        }
    }
}
