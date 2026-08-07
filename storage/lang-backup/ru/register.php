<?php

// Register page (RU).

return [
    'title' => 'Регистрация — ARCHİ',

    'head' => [
        'tag' => 'Присоединиться к ARCHİ',
        'title' => 'Зарегистрироваться',
        'subtitle' => 'Выберите тип аккаунта — покупатель, продавец или мастер.',
    ],

    'success' => 'Регистрация завершена!',

    'roles' => [
        'label' => 'Тип аккаунта',
        'buyer' => [
            'title' => 'Покупатель',
            'desc' => 'Покупайте материалы, находите мастеров, заказывайте.',
        ],
        'seller' => [
            'title' => 'Продавец',
            'desc' => 'Размещайте товары и продавайте.',
        ],
        'master' => [
            'title' => 'Мастер / специалист',
            'desc' => 'Оказывайте услуги, находите клиентов.',
        ],
    ],

    'form' => [
        'first_name_label' => 'Имя',
        'first_name_placeholder' => 'Ваше имя',
        'last_name_label' => 'Фамилия',
        'last_name_placeholder' => 'Ваша фамилия',
        'company_label' => 'Компания / бренд',
        'company_placeholder' => 'Например: ARCHI Build MMC',
        'specialization_label' => 'Специализация',
        'city_label' => 'Город',
        'select_placeholder' => 'Выберите',
        'email_label' => 'E-mail',
        'email_placeholder' => 'email@example.com',
        'phone_label' => 'Телефон',
        'phone_placeholder' => '+994 50 000 00 00',
        'password_label' => 'Пароль',
        'password_placeholder' => 'Минимум 6 символов',
        'password_confirm_label' => 'Повторите пароль',
        'password_confirm_placeholder' => 'Повторите пароль',
        'terms_link' => 'Условия использования',
        'terms_and' => 'и',
        'privacy_link' => 'Политика конфиденциальности',
        'terms_agree' => '— принимаю',
        'submit' => 'Зарегистрироваться',
        'info_note' => 'После регистрации как специалист или продавец вам будет предложено заполнить дополнительные данные профиля.',
        'have_account' => 'Уже есть аккаунт?',
        'sign_in' => 'Войти',
    ],

    'specializations' => [
        'tiler' => 'Плиточник',
        'electrician' => 'Электрик',
        'plumber' => 'Сантехник',
        'painter' => 'Маляр',
        'drywaller' => 'Гипсокартонщик',
        'architect' => 'Архитектор',
        'interior_designer' => 'Дизайнер интерьера',
        'other' => 'Другое',
    ],

    'cities' => [
        'baku' => 'Баку',
        'sumgayit' => 'Сумгаит',
        'ganja' => 'Гянджа',
        'mingachevir' => 'Мингячевир',
        'shirvan' => 'Ширван',
        'other' => 'Другое',
    ],

    'pending_message' => 'Регистрация завершена! Вы сможете войти после подтверждения аккаунта администратором.',
];
