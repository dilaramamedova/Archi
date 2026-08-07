<?php

return [
    'title' => 'Заказы — Бизнес-панель',
    'heading' => 'Заказы',
    'crumbs' => ['panel' => 'Бизнес-панель', 'current' => 'Заказы', 'sep' => '/'],
    'status' => ['published' => 'Активен', 'view_profile' => 'Отчёт ↗'],
    'progress' => ['label' => 'Заполненность профиля', 'value' => '85%', 'note' => 'Добавьте видео — +15%'],

    'filters' => [
        'pending' => 'Новые',
        'processing' => 'Собирается',
        'shipped' => 'У курьера',
        'delivered' => 'Доставлен',
        'cancelled' => 'Отмена',
        'search_placeholder' => '№ заказа или клиент',
    ],

    'badge' => [
        'pending' => 'Новый',
        'processing' => 'Собирается',
        'shipped' => 'У курьера',
        'delivered' => 'Доставлен',
        'cancelled' => 'Отменён',
    ],

    'card' => [
        'order_no' => 'Заказ №',
        'products_count' => 'товаров',
        'message' => 'Написать сообщение',
        'details' => 'Подробнее',
        'take_processing' => 'Взять в сборку',
        'give_courier' => 'Передать курьеру',
        'tracking' => 'Ссылка отслеживания',
        'invoice' => 'Накладная',
        'note_pending' => 'Нужно собрать: как можно скорее',
        'note_processing' => 'Готовится к передаче курьеру',
        'note_shipped' => 'У курьера — ожидается доставка',
        'note_delivered' => 'Доставлен',
        'note_cancelled' => 'Заказ отменён',
    ],

    'empty' => [
        'title' => 'Заказов пока нет',
        'desc' => 'Когда ваши товары будут опубликованы в каталоге, заказы появятся здесь.',
    ],
];

