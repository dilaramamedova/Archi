<?php

// Cart page (EN).

return [
    'title' => 'Cart — ARCHİ',
    'breadcrumb' => 'Cart',
    'heading' => 'My cart',
    'money' => ':amount ₼',

    'empty' => [
        'text' => 'Your cart is empty.',
        'cta' => 'View deals',
    ],

    'items' => [
        'unit_price' => 'Unit price: :price ₼',
        'remove' => 'Remove',
    ],

    'promo' => [
        'label' => 'Promo code',
        'apply' => 'Apply',
        'try' => 'Try:',
        'applied' => '✓ :code applied — :label',
        'min_required' => 'Code :code: a minimum order of :min ₼ is required',
        'unknown' => 'No such promo code',
    ],

    'promos' => [
        'archi15' => '15% off',
        'yeni10' => '10% off',
        'qis20' => '20% off (min 200 ₼)',
    ],

    'summary' => [
        'title' => 'Order summary',
        'subtotal' => 'Subtotal (:count items)',
        'discount' => 'Discount (:code)',
        'delivery' => 'Delivery',
        'delivery_free' => 'Free',
        'total' => 'Total',
        'checkout' => 'Place the order',
    ],

    'alert' => [
        'empty' => 'The cart is empty.',
        'done' => 'Order accepted! Total: :total',
    ],
];
