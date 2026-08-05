<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'site_logo' => '/assets/logo-archi-black.png',
            'site_logo_white' => '/assets/logo-archi-white.png',
            'site_name' => json_encode(['az' => 'ARCHİ', 'ru' => 'ARCHI', 'en' => 'ARCHI']),

            'sale_marquee_enabled' => '1',
            'sale_marquee_text' => json_encode(['az' => 'SALE • OUTLET', 'ru' => 'SALE • OUTLET', 'en' => 'SALE • OUTLET']),

            'mega_spec_promo_image' => '/assets/hero-promo-power-tools.png',
            'mega_spec_promo_text' => json_encode([
                'az' => 'Hardan başlayacağınızı bilmirsiniz? Pulsuz konsultasiya xidmətindən yararlanın.',
                'ru' => 'Не знаете с чего начать? Воспользуйтесь бесплатной консультацией.',
                'en' => 'Don\'t know where to start? Use our free consultation service.',
            ]),
            'mega_spec_promo_button_text' => json_encode(['az' => 'Pulsuz konsultasiya', 'ru' => 'Бесплатная консультация', 'en' => 'Free consultation']),
            'mega_spec_promo_button_url' => '/specialists',

            'about_hero_tag' => json_encode(['az' => 'Haqqımızda', 'ru' => 'О нас', 'en' => 'About us']),
            'about_hero_title' => json_encode(['az' => 'Tikinti və təmir — bir platformada', 'ru' => 'Строительство и ремонт — на одной платформе', 'en' => 'Construction and renovation — on one platform']),
            'about_hero_subtitle' => json_encode([
                'az' => 'ARCHİ — materialdan etibarlı ustaya qədər təmirin bütün mərhələlərini bir yerə toplayan Azərbaycan platformasıdır.',
                'ru' => 'ARCHI — азербайджанская платформа, объединяющая все этапы ремонта.',
                'en' => 'ARCHI is an Azerbaijani platform that brings together all stages of renovation.',
            ]),

            'about_story_image' => '/assets/renovation-before-after.png',
            'about_story_image_alt' => json_encode(['az' => 'Təmir mərhələsində mənzil', 'ru' => 'Квартира на этапе ремонта', 'en' => 'Apartment during renovation']),
            'about_story_tag' => json_encode(['az' => 'Bizim hekayəmiz', 'ru' => 'Наша история', 'en' => 'Our story']),
            'about_story_title' => json_encode(['az' => 'Niyə ARCHİ yarandı?', 'ru' => 'Почему появился ARCHI?', 'en' => 'Why was ARCHI created?']),
            'about_story_paragraph_1' => json_encode([
                'az' => 'Təmirə başlayan hər kəs eyni problemlə üzləşir: material haradan alınmalı, usta necə tapılmalı, büdcə nə qədər olmalı?',
                'ru' => 'Каждый, кто начинает ремонт, сталкивается с одной и той же проблемой.',
                'en' => 'Everyone who starts a renovation faces the same problem.',
            ]),
            'about_story_paragraph_2' => json_encode([
                'az' => '2021-ci ildə ARCHİ-ni məhz buna görə qurduq — kataloq, reytinqli ustalar, təmir kalkulyatoru və pulsuz konsultasiya bir platformada.',
                'ru' => 'В 2021 году мы создали ARCHI именно для этого.',
                'en' => 'In 2021, we created ARCHI for exactly this reason.',
            ]),
            'about_story_author_initials' => 'LA',
            'about_story_author_name' => 'Lala Abdullayeva',
            'about_story_author_role' => json_encode(['az' => 'Təsisçi & CEO', 'ru' => 'Основатель & CEO', 'en' => 'Founder & CEO']),

            'about_values_tag' => json_encode(['az' => 'Dəyərlərimiz', 'ru' => 'Наши ценности', 'en' => 'Our values']),
            'about_values_title' => json_encode(['az' => 'Nəyə inanırıq?', 'ru' => 'Во что мы верим?', 'en' => 'What do we believe in?']),

            'about_cta_title' => json_encode(['az' => 'ARCHİ ailəsinə qoşul', 'ru' => 'Присоединяйся к семье ARCHI', 'en' => 'Join the ARCHI family']),
            'about_cta_subtitle' => json_encode([
                'az' => 'İstər usta ol, istər məhsullarını sat — minlərlə müştəriyə bir addımda çat.',
                'ru' => 'Станьте мастером или продавайте свои товары.',
                'en' => 'Become a master or sell your products.',
            ]),
            'about_cta_button1_text' => json_encode(['az' => 'Usta ol', 'ru' => 'Стать мастером', 'en' => 'Become a master']),
            'about_cta_button1_url' => '/register',
            'about_cta_button2_text' => json_encode(['az' => 'Satıcı ol', 'ru' => 'Стать продавцом', 'en' => 'Become a seller']),
            'about_cta_button2_url' => '/sell',
        ];

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
