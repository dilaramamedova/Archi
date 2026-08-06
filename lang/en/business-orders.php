<?php

return [
    'title' => 'Orders — Business panel',
    'heading' => 'Orders',
    'crumbs' => ['panel' => 'Business panel', 'current' => 'Orders', 'sep' => '/'],
    'status' => ['published' => 'Active', 'view_profile' => 'Report ↗'],
    'progress' => ['label' => 'Profile completeness', 'value' => '85%', 'note' => 'Add a video — +15%'],

    'filters' => [
        'pending' => 'New',
        'processing' => 'Processing',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
        'search_placeholder' => 'Order no. or customer',
    ],

    'badge' => [
        'pending' => 'New',
        'processing' => 'Processing',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
    ],

    'card' => [
        'order_no' => 'Order no.',
        'products_count' => 'products',
        'message' => 'Send message',
        'details' => 'Details',
        'take_processing' => 'Start processing',
        'give_courier' => 'Hand to courier',
        'tracking' => 'Tracking link',
        'invoice' => 'Invoice',
        'note_pending' => 'To be packed: as soon as possible',
        'note_processing' => 'Being prepared for courier pickup',
        'note_shipped' => 'Shipped — awaiting delivery',
        'note_delivered' => 'Delivered',
        'note_cancelled' => 'Order has been cancelled',
    ],

    'empty' => [
        'title' => 'No orders yet',
        'desc' => 'Once your products are published in the catalog, orders will appear here.',
    ],
];

