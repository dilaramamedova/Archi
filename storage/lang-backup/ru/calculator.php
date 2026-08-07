<?php

// Quick calculator page (RU).

return [
    'title' => 'Быстрый калькулятор ремонта — ARCHİ',
    'breadcrumb' => 'Быстрый калькулятор',

    'head' => [
        'tag' => 'Быстрый калькулятор',
        'title' => 'Примерная стоимость ремонта',
        'subtitle' => 'Несколько параметров — 3 варианта бюджета.',
    ],

    'object' => [
        'label' => 'Тип объекта',
        'apartment' => 'Квартира',
        'house' => 'Частный дом',
        'office' => 'Офис',
        'commercial' => 'Коммерция',
    ],

    'area' => [
        'label' => 'Площадь объекта',
        'unit' => 'м²',
    ],

    'type' => [
        'label' => 'Тип ремонта',
        'shell' => 'Черновой',
        'cosmetic' => 'Косметический',
        'major' => 'Капитальный',
        'turnkey' => 'Под ключ',
    ],

    'rooms' => [
        'label' => 'Количество комнат',
        'studio' => 'Студия',
        'one' => '1',
        'two' => '2',
        'three' => '3',
        'four_plus' => '4+',
    ],

    'level' => [
        'label' => 'Уровень материалов',
        'economy' => 'Эконом',
        'standard' => 'Стандарт',
        'premium' => 'Премиум',
    ],

    'result' => [
        'label' => 'Примерная стоимость для :area м²',
        'currency' => 'AZN и выше',
        'recommended' => 'Рекомендуем',
    ],

    'actions' => [
        'next' => 'Получить подробный расчёт  →',
        'pdf' => 'Скачать PDF',
        'whatsapp' => 'Отправить в WhatsApp',
        'save' => 'Сохранить',
    ],

    'messages' => [
        'saved' => 'Расчёт сохранён.',
        'whatsapp' => 'Расчёт ремонта ARCHİ: :area м² · :level · ~:price AZN',
    ],
];
