<?php

namespace Database\Seeders;

use App\Models\AboutStat;
use App\Models\AboutValue;
use Illuminate\Database\Seeder;

class AboutSeeder extends Seeder
{
    public function run(): void
    {
        $stats = [
            ['value' => ['az' => '860+', 'ru' => '860+', 'en' => '860+'], 'label' => ['az' => 'məhsul kataloqda', 'ru' => 'товаров в каталоге', 'en' => 'products in catalog'], 'sort_order' => 1],
            ['value' => ['az' => '248', 'ru' => '248', 'en' => '248'], 'label' => ['az' => 'təsdiqlənmiş usta', 'ru' => 'подтверждённых мастеров', 'en' => 'verified masters'], 'sort_order' => 2],
            ['value' => ['az' => '12 000+', 'ru' => '12 000+', 'en' => '12,000+'], 'label' => ['az' => 'tamamlanmış sifariş', 'ru' => 'завершённых заказов', 'en' => 'completed orders'], 'sort_order' => 3],
            ['value' => ['az' => '2021', 'ru' => '2021', 'en' => '2021'], 'label' => ['az' => 'ildən bazardayıq', 'ru' => 'с нами на рынке', 'en' => 'on the market since'], 'sort_order' => 4],
        ];

        foreach ($stats as $stat) {
            AboutStat::create($stat);
        }

        $values = [
            [
                'icon' => 'icon-shield-check.svg',
                'title' => ['az' => 'Etibar', 'ru' => 'Доверие', 'en' => 'Trust'],
                'description' => ['az' => 'Hər usta sənədlə yoxlanılır, hər satıcı təsdiqlənir. Reytinq və rəylər real sifarişlərə əsaslanır.', 'ru' => 'Каждый мастер проверяется документально, каждый продавец подтверждается.', 'en' => 'Every master is verified with documents, every seller is confirmed.'],
                'sort_order' => 1,
            ],
            [
                'icon' => 'icon-star-outline.svg',
                'title' => ['az' => 'Keyfiyyət', 'ru' => 'Качество', 'en' => 'Quality'],
                'description' => ['az' => 'Kataloqa yalnız sertifikatlı materiallar əlavə olunur. Zəif reytinqli xidmətlər platformada qalmır.', 'ru' => 'В каталог добавляются только сертифицированные материалы.', 'en' => 'Only certified materials are added to the catalog.'],
                'sort_order' => 2,
            ],
            [
                'icon' => 'icon-eye.svg',
                'title' => ['az' => 'Şəffaflıq', 'ru' => 'Прозрачность', 'en' => 'Transparency'],
                'description' => ['az' => 'Qiymətlər açıqdır, smeta kalkulyatorla əvvəlcədən hesablanır. Gizli xərc yoxdur.', 'ru' => 'Цены открыты, смета рассчитывается калькулятором заранее.', 'en' => 'Prices are transparent, estimates are calculated in advance.'],
                'sort_order' => 3,
            ],
            [
                'icon' => 'icon-speech-bubble.svg',
                'title' => ['az' => 'Dəstək', 'ru' => 'Поддержка', 'en' => 'Support'],
                'description' => ['az' => 'Pulsuz konsultasiya və 7/24 yardım mərkəzi — təmirin hər mərhələsində yanınızdayıq.', 'ru' => 'Бесплатная консультация и центр помощи 24/7.', 'en' => 'Free consultation and 24/7 help center.'],
                'sort_order' => 4,
            ],
        ];

        foreach ($values as $value) {
            AboutValue::create($value);
        }
    }
}
