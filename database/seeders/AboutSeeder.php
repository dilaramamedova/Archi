<?php

namespace Database\Seeders;

use App\Models\AboutStat;
use App\Models\AboutStep;
use App\Models\AboutTeamMember;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class AboutSeeder extends Seeder
{
    public function run(): void
    {
        AboutStat::query()->delete();
        $stats = [
            ['value' => ['az' => '1 200+', 'ru' => '1 200+', 'en' => '1,200+'], 'label' => ['az' => 'Təsdiqlənmiş usta və mütəxəssis', 'ru' => 'Проверенных мастеров и специалистов', 'en' => 'Verified masters and specialists'], 'sort_order' => 1],
            ['value' => ['az' => '45 000+', 'ru' => '45 000+', 'en' => '45,000+'], 'label' => ['az' => 'Kataloqdakı material', 'ru' => 'Товаров в каталоге', 'en' => 'Products in catalog'], 'sort_order' => 2],
            ['value' => ['az' => '18 400', 'ru' => '18 400', 'en' => '18,400'], 'label' => ['az' => 'Tamamlanmış sifariş', 'ru' => 'Завершённых заказов', 'en' => 'Completed orders'], 'sort_order' => 3],
            ['value' => ['az' => '4.8 / 5', 'ru' => '4.8 / 5', 'en' => '4.8 / 5'], 'label' => ['az' => 'Orta müştəri reytinqi', 'ru' => 'Средний рейтинг клиентов', 'en' => 'Average customer rating'], 'sort_order' => 4],
        ];
        foreach ($stats as $stat) {
            AboutStat::create($stat);
        }

        AboutStep::query()->delete();
        $steps = [
            [
                'step_number' => 1,
                'title' => ['az' => 'Axtar', 'ru' => 'Найди', 'en' => 'Search'],
                'description' => ['az' => 'Material və ya mütəxəssis axtar, kateqoriya və büdcəyə görə daralt.', 'ru' => 'Найди материал или специалиста, отфильтруй по категории и бюджету.', 'en' => 'Search for materials or specialists, narrow by category and budget.'],
                'sort_order' => 1,
            ],
            [
                'step_number' => 2,
                'title' => ['az' => 'Müqayisə et', 'ru' => 'Сравни', 'en' => 'Compare'],
                'description' => ['az' => 'Qiymət, reytinq, rəy və portfolionu yan-yana gör.', 'ru' => 'Сравни цену, рейтинг, отзывы и портфолио.', 'en' => 'Compare price, rating, reviews and portfolio side by side.'],
                'sort_order' => 2,
            ],
            [
                'step_number' => 3,
                'title' => ['az' => 'Sifariş ver', 'ru' => 'Закажи', 'en' => 'Order'],
                'description' => ['az' => 'Səbətə at və ya ustaya birbaşa yaz — vasitəçi yoxdur.', 'ru' => 'Добавь в корзину или напиши мастеру напрямую — посредников нет.', 'en' => 'Add to cart or contact the master directly — no middlemen.'],
                'sort_order' => 3,
            ],
            [
                'step_number' => 4,
                'title' => ['az' => 'İşə başla', 'ru' => 'Начни', 'en' => 'Start'],
                'description' => ['az' => 'Çatdırılmanı izlə, iş bitəndə rəyini paylaş.', 'ru' => 'Отслеживай доставку, оставь отзыв по завершении.', 'en' => 'Track delivery, share your review when done.'],
                'sort_order' => 4,
            ],
        ];
        foreach ($steps as $step) {
            AboutStep::create($step);
        }

        AboutTeamMember::query()->delete();
        $teamMembers = [
            [
                'name' => ['az' => 'Aysel Quliyeva', 'ru' => 'Айсель Кулиева', 'en' => 'Aysel Guliyeva'],
                'role' => ['az' => 'Həmtəsisçi & CEO', 'ru' => 'Сооснователь & CEO', 'en' => 'Co-founder & CEO'],
                'sort_order' => 1,
            ],
            [
                'name' => ['az' => 'Rəşad Əliyev', 'ru' => 'Рашад Алиев', 'en' => 'Rashad Aliyev'],
                'role' => ['az' => 'Həmtəsisçi & Məhsul', 'ru' => 'Сооснователь & Продукт', 'en' => 'Co-founder & Product'],
                'sort_order' => 2,
            ],
            [
                'name' => ['az' => 'Nigar Hüseynova', 'ru' => 'Нигяр Гусейнова', 'en' => 'Nigar Huseynova'],
                'role' => ['az' => 'Mütəxəssis şəbəkəsi', 'ru' => 'Сеть специалистов', 'en' => 'Specialist Network'],
                'sort_order' => 3,
            ],
            [
                'name' => ['az' => 'Elvin Məmmədov', 'ru' => 'Эльвин Мамедов', 'en' => 'Elvin Mammadov'],
                'role' => ['az' => 'Texnologiya', 'ru' => 'Технологии', 'en' => 'Technology'],
                'sort_order' => 4,
            ],
        ];
        foreach ($teamMembers as $member) {
            AboutTeamMember::create($member);
        }

        $settings = [
            'about_hero_title' => ['az' => 'Tikinti və təmiri hamı üçün sadələşdiririk', 'ru' => 'Упрощаем строительство и ремонт для всех', 'en' => 'We simplify construction and renovation for everyone'],
            'about_hero_subtitle' => ['az' => 'ARCHİ materialdan etibarlı ustaya qədər hər şeyi bir platformada birləşdirir. Məqsədimiz — təmir prosesini şəffaf, proqnozlaşdırıla bilən və stresssiz etmək.', 'ru' => 'ARCHI объединяет всё — от материалов до надёжных мастеров — на одной платформе. Наша цель — сделать ремонт прозрачным, предсказуемым и без стресса.', 'en' => 'ARCHI unites everything from materials to trusted masters on one platform. Our goal is to make renovation transparent, predictable and stress-free.'],
            'about_mission_tag' => ['az' => 'Missiyamız', 'ru' => 'Наша миссия', 'en' => 'Our Mission'],
            'about_mission_title' => ['az' => 'Təmirə başlamaq üçün ekspert olmaq lazım deyil', 'ru' => 'Чтобы начать ремонт, не нужно быть экспертом', 'en' => 'You don\'t need to be an expert to start renovating'],
            'about_mission_p1' => ['az' => 'Azərbaycanda təmir çox vaxt tanışlıq üzərində qurulur: kimin etibarlı usta olduğunu, materialın real qiymətinin nə qədər olduğunu əvvəlcədən bilmək çətindir. Nəticədə büdcə pozulur, işlər uzanır.', 'ru' => 'В Азербайджане ремонт часто строится на знакомствах: заранее трудно узнать, кто надёжный мастер и какова реальная цена материалов. В итоге бюджет нарушается, работы затягиваются.', 'en' => 'In Azerbaijan, renovation is often based on word of mouth: it\'s hard to know in advance who is a reliable master and what the real price of materials is. As a result, budgets are exceeded and work drags on.'],
            'about_mission_p2' => ['az' => 'ARCHİ bu qeyri-müəyyənliyi aradan qaldırır — materialı, ustanı və qiyməti bir yerdə, açıq şəkildə göstərir.', 'ru' => 'ARCHI устраняет эту неопределённость — показывает материалы, мастеров и цены в одном месте, открыто.', 'en' => 'ARCHI eliminates this uncertainty — it shows materials, masters and prices in one place, openly.'],
            'about_mission_b1' => ['az' => 'Hər usta sənəd yoxlamasından keçir', 'ru' => 'Каждый мастер проходит проверку документов', 'en' => 'Every master passes document verification'],
            'about_mission_b2' => ['az' => 'Qiymətlər əvvəlcədən görünür — gizli xərc yoxdur', 'ru' => 'Цены видны заранее — скрытых расходов нет', 'en' => 'Prices are visible upfront — no hidden costs'],
            'about_mission_b3' => ['az' => 'Ödəniş iş təhvil verilənə qədər qorunur', 'ru' => 'Оплата защищена до сдачи работы', 'en' => 'Payment is protected until the work is delivered'],
            'about_what_tag' => ['az' => 'Nə təklif edirik', 'ru' => 'Что мы предлагаем', 'en' => 'What We Offer'],
            'about_what_title' => ['az' => 'Bir platforma, üç iş', 'ru' => 'Одна платформа, три задачи', 'en' => 'One platform, three solutions'],
            'about_how_tag' => ['az' => 'Necə işləyir', 'ru' => 'Как это работает', 'en' => 'How It Works'],
            'about_how_title' => ['az' => 'Dörd addımda təmir', 'ru' => 'Ремонт за четыре шага', 'en' => 'Renovation in four steps'],
            'about_team_tag' => ['az' => 'Komanda', 'ru' => 'Команда', 'en' => 'Team'],
            'about_team_title' => ['az' => 'Komandamız haqqında', 'ru' => 'О нашей команде', 'en' => 'About our team'],
            'about_join_tag' => ['az' => 'Bizə qoşul', 'ru' => 'Присоединяйтесь', 'en' => 'Join Us'],
            'about_join_title' => ['az' => 'ARCHİ-də sən də qazan', 'ru' => 'Зарабатывайте с ARCHI', 'en' => 'Earn with ARCHI'],
            'about_contact_tag' => ['az' => 'Əlaqə', 'ru' => 'Контакт', 'en' => 'Contact'],
            'about_contact_title' => ['az' => 'Bizimlə əlaqə saxla', 'ru' => 'Свяжитесь с нами', 'en' => 'Get in touch'],
            'about_contact_address_line1' => ['az' => 'Bakı, Nəsimi r., Azadlıq pr. 12', 'ru' => 'Баку, Насиминский р-н, пр. Азадлыг 12', 'en' => 'Baku, Nasimi dist., Azadlig ave. 12'],
            'about_contact_address_line2' => ['az' => 'ARCHİ Business Center, 4-cü mərtəbə', 'ru' => 'ARCHİ Business Center, 4-й этаж', 'en' => 'ARCHİ Business Center, 4th floor'],
            'about_contact_phone' => '+994 12 555 00 12',
            'about_contact_hours' => ['az' => 'B.e–Cümə · 09:00–19:00', 'ru' => 'Пн–Пт · 09:00–19:00', 'en' => 'Mon–Fri · 09:00–19:00'],
            'about_contact_email' => 'salam@archi.az',
            'about_contact_email_b2b' => ['az' => 'Tərəfdaşlıq üçün: b2b@archi.az', 'ru' => 'Для партнёрства: b2b@archi.az', 'en' => 'For partnerships: b2b@archi.az'],
            'about_cta1_text' => ['az' => 'Kataloqa bax', 'ru' => 'Смотреть каталог', 'en' => 'Browse Catalog'],
            'about_cta2_text' => ['az' => 'Mütəxəssis tap', 'ru' => 'Найти специалиста', 'en' => 'Find a Specialist'],
        ];

        foreach ($settings as $key => $value) {
            Setting::set($key, $value);
        }
    }
}
