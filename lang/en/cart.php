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
        'increase' => 'Increase quantity',
        'decrease' => 'Decrease quantity',
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

    'checkout_form' => [
        'title' => 'Delivery details',
        'name' => 'Full name',
        'phone' => 'Phone',
        'address' => 'Address',
        'notes' => 'Notes (optional)',
        'submit' => 'Confirm order',
        'cancel' => 'Back',
        'name_required' => 'Please enter your name',
        'phone_required' => 'Please enter your phone',
        'address_required' => 'Please enter your address',
        'error' => 'Something went wrong. Please try again.',
        'sending' => 'Sending...',
    ],

    'order_success' => [
        'title' => 'Order #:number — ARCHİ',
        'heading' => 'Your order has been placed!',
        'message' => 'Thank you! Your order has been successfully registered. We will contact you shortly.',
        'order_number' => 'Order number',
        'total' => 'Total',
        'status' => 'Status',
        'status_pending' => 'Pending',
        'back_home' => 'Home page',
        'back_catalog' => 'Browse catalog',
    ],
];
