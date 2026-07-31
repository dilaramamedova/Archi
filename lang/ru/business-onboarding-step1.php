<?php

// Business onboarding step 1 — company details (RU).

return [
    'title' => 'Бизнес — Заполнение · Шаг 1: Данные компании',

    'back' => '←  Вернуться в панель магазина',
    'heading' => 'Данные компании',

    'steps' => [
        'company' => 'Данные компании',
        'contact' => 'Контакты',
        'product' => 'Первый товар',
    ],

    'form' => [
        'legal_name_label' => 'Юридическое название *',
        'legal_name_placeholder' => 'Название ООО / ИП',
        'brand_label' => 'Название бренда (магазина) *',
        'brand_placeholder' => 'Название, видимое на сайте',
        'tax_id_label' => 'ИНН *',
        'tax_id_placeholder' => '10 цифр',
        'city_label' => 'Город *',
        'city_placeholder' => 'Выберите',
        'address_label' => 'Юридический адрес',
        'address_placeholder' => 'Улица, дом',
        'phone_label' => 'Телефон',
        'phone_placeholder' => '+994 50 000 00 00',
        'showroom_label' => 'Адрес шоурума',
        'showroom_placeholder' => 'Адрес шоурума',
        'logo_label' => 'Логотип',
        'logo_upload' => '＋  Загрузить логотип (PNG/SVG)',
        'about_label' => 'Краткое описание компании',
        'about_placeholder' => 'Расскажите о себе клиентам — что продаёте, сколько лет на рынке…',
    ],

    'actions' => [
        'save' => 'Сохранить и продолжить  →',
        'later' => 'Продолжу позже',
    ],

    'side' => [
        'progress_title' => 'Заполнение: 0% → 33%',
        'progress_note' => 'После этого шага ваш магазин будет готов на 50%.',
        'tip_title' => '💡 Совет',
        'tip_text' => 'Указывайте ИНН точно — расхождение при проверке документов чаще всего и становится причиной задержки. Название бренда можно изменить позже.',
    ],
];
