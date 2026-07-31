<?php

// Cart page (RU).

return [
    'title' => 'Корзина — ARCHİ',
    'breadcrumb' => 'Корзина',
    'heading' => 'Моя корзина',
    'money' => ':amount ₼',

    'empty' => [
        'text' => 'Ваша корзина пуста.',
        'cta' => 'Смотреть акции',
    ],

    'items' => [
        'unit_price' => 'Цена за единицу: :price ₼',
        'remove' => 'Удалить',
        'increase' => 'Увеличить количество',
        'decrease' => 'Уменьшить количество',
    ],

    'promo' => [
        'label' => 'Промокод',
        'apply' => 'Применить',
        'try' => 'Попробуйте:',
        'applied' => '✓ :code применён — :label',
        'min_required' => 'Код :code: требуется заказ минимум на :min ₼',
        'unknown' => 'Такого промокода нет',
    ],

    'promos' => [
        'archi15' => 'скидка 15%',
        'yeni10' => 'скидка 10%',
        'qis20' => 'скидка 20% (мин. 200 ₼)',
    ],

    'summary' => [
        'title' => 'Итоги заказа',
        'subtotal' => 'Промежуточный итог (товаров: :count)',
        'discount' => 'Скидка (:code)',
        'delivery' => 'Доставка',
        'delivery_free' => 'Бесплатно',
        'total' => 'Итого',
        'checkout' => 'Оформить заказ',
    ],

    'alert' => [
        'empty' => 'Корзина пуста.',
        'done' => 'Заказ принят! Итого: :total',
    ],
];
