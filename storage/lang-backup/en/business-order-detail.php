<?php

return [
    'title' => 'Order details — Business panel',
    'crumbs' => ['panel' => 'Business panel', 'current' => 'Order', 'sep' => '/'],
    'status' => ['published' => 'Order', 'view_profile' => 'Invoice ↗'],
    'progress' => ['label' => 'Profile completeness', 'value' => '85%', 'note' => 'Add a video — +15%'],

    'stepper' => [
        'title' => 'Fulfillment stage',
        'received_at' => 'Order received on :date',
        'step_pending' => 'Received',
        'step_processing' => 'Processing',
        'step_shipped' => 'Shipped',
        'step_delivered' => 'Delivered',
        'cancelled' => 'Order has been cancelled',
    ],

    'customer' => [
        'title' => 'Customer',
        'name' => 'Name',
        'phone' => 'Phone',
        'email' => 'Email',
        'prev_orders' => 'Previous orders',
        'orders_count' => 'orders',
    ],

    'delivery' => [
        'title' => 'Delivery',
        'address' => 'Address',
        'note' => 'Note',
        'method' => 'Method',
        'method_value' => 'Standard · 3–5 business days',
        'city' => 'City',
    ],

    'items' => [
        'title' => 'Items to pack',
        'qty' => 'pcs',
        'stock_left' => ':count pcs in stock',
        'stock_low' => ':count pcs in stock — running low',
        'subtotal' => 'Subtotal',
        'discount' => 'Discount (:code) — covered by the platform',
        'commission' => 'Commission (8%)',
        'payout' => 'Your payout',
    ],

    'actions' => [
        'hint' => 'Change the status once packing is done — the customer is notified automatically.',
        'cancel' => 'Cancel order',
        'print' => 'Print invoice',
        'to_processing' => 'Start processing',
        'to_shipped' => 'Packed — hand to courier',
        'to_delivered' => 'Mark as delivered',
        'cancel_confirm_title' => 'Cancel order',
        'cancel_confirm_body' => 'Are you sure you want to cancel this order? The customer will be notified.',
    ],
];
