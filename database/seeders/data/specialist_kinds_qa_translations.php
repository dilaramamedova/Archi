<?php

declare(strict_types=1);

/*
 * Follow-up copy for the Briqadir / Makler kinds (QA pass, 2026-11-19 —
 * docs/specialist-kinds.md): validation attribute names and messages, singular kind
 * labels for the admin, and kind-neutral cabinet headings (a Makler is not an «usta»).
 *
 * Read by TranslationsSeeder (merged after the main array, so it wins on reseed) and by
 * 2026_11_19_120400_specialist_kinds_qa_fixes, which overwrites ONLY the keys listed in
 * that migration's OVERWRITE constant and inserts the rest if missing.
 *
 * @return array<string, array<string, array{az: string, ru: string, en: string}>>
 */
$heading = ['az' => 'Profilin redaktəsi', 'ru' => 'Редактирование профиля', 'en' => 'Edit profile'];
$panel = ['az' => 'Mütəxəssis paneli', 'ru' => 'Панель специалиста', 'en' => 'Specialist panel'];

return [
    'specialists' => [
        // Singular kind labels — one row in the admin is one person, not «Briqadirlər».
        'kind.architect' => ['az' => 'Arxitektor', 'ru' => 'Архитектор', 'en' => 'Architect'],
        'kind.designer' => ['az' => 'İnteryer dizayner', 'ru' => 'Дизайнер интерьера', 'en' => 'Interior designer'],
        'kind.master' => ['az' => 'Usta', 'ru' => 'Мастер', 'en' => 'Craftsman'],
        'kind.company' => ['az' => 'Tikinti şirkəti', 'ru' => 'Строительная компания', 'en' => 'Construction company'],
        'kind.foreman' => ['az' => 'Briqadir', 'ru' => 'Бригадир', 'en' => 'Foreman'],
        'kind.realtor' => ['az' => 'Makler', 'ru' => 'Риелтор', 'en' => 'Realtor'],
    ],

    'register' => [
        'head.subtitle' => ['az' => 'Hesab növünü seç — alıcı, satıcı, usta/mütəxəssis, briqadir və ya makler kimi davam et.', 'ru' => 'Выберите тип аккаунта — покупатель, продавец, мастер/специалист, бригадир или риелтор.', 'en' => 'Choose an account type — buyer, seller, craftsman/specialist, foreman or realtor.'],
        'form.city_required' => ['az' => 'Şəhəri seçin', 'ru' => 'Выберите город', 'en' => 'Please select a city'],
        'form.specialty_required' => ['az' => 'İxtisası seçin', 'ru' => 'Выберите специализацию', 'en' => 'Please select a specialization'],
    ],

    'specialist' => [
        'about.eyebrow' => ['az' => 'Haqqında', 'ru' => 'О специалисте', 'en' => 'About'],
    ],

    'specialist-owner' => [
        'about.eyebrow' => ['az' => 'Haqqında', 'ru' => 'О специалисте', 'en' => 'About'],
    ],

    'specialist-cabinet' => [
        'heading' => $heading,
        'crumbs.panel' => $panel,
        'crew.member_years_ph' => ['az' => 'Təcrübə, il', 'ru' => 'Опыт, лет', 'en' => 'Exp., yrs'],
        'crew.member_name_required' => ['az' => 'Dəstə üzvünün adını yazın', 'ru' => 'Укажите имя участника бригады', 'en' => 'Enter the crew member’s name'],
        'realtor.deal_types_required' => ['az' => 'Ən azı bir əməliyyat növü seçin', 'ru' => 'Выберите хотя бы один вид сделки', 'en' => 'Pick at least one deal type'],
    ],

    'specialist-cabinet-notifications' => [
        'heading' => $heading,
        'crumbs.panel' => $panel,
        'title' => ['az' => 'Profilin redaktəsi — Bildirişlər — ARCHİ', 'ru' => 'Редактирование профиля — Уведомления — ARCHİ', 'en' => 'Edit profile — Notifications — ARCHİ'],
    ],
    'specialist-cabinet-portfolio' => ['heading' => $heading, 'crumbs.panel' => $panel],
    'specialist-cabinet-reviews' => ['heading' => $heading, 'crumbs.panel' => $panel],
    'specialist-cabinet-schedule' => [
        'heading' => $heading,
        'crumbs.panel' => $panel,
        'title' => ['az' => 'Profilin redaktəsi — İş qrafiki — ARCHİ', 'ru' => 'Редактирование профиля — График работы — ARCHİ', 'en' => 'Edit profile — Work schedule — ARCHİ'],
    ],
    'specialist-cabinet-security' => ['heading' => $heading, 'crumbs.panel' => $panel],
    'specialist-cabinet-services' => ['heading' => $heading, 'crumbs.panel' => $panel],
];
