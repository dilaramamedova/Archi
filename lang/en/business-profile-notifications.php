<?php

// Business cabinet — notifications tab.

return [
    'title' => 'Edit business profile — Notifications — ARCHİ',

    'crumbs' => [
        'panel' => 'Business panel',
        'sep' => '/',
        'current' => 'Profile editing',
    ],

    'heading' => 'Edit business profile',

    'status' => [
        'published' => 'Published',
        'view_profile' => 'View profile ↗',
    ],

    'nav' => [
        'company' => 'Company details',
        'contact' => 'Contact details',
        'showrooms' => 'Showrooms',
        'showrooms_count' => '3',
        'products' => 'Products',
        'products_count' => '1,240',
        'notifications' => 'Notifications',
        'security' => 'Security',
    ],

    'progress' => [
        'label' => 'Profile completeness',
        'value' => '85%',
        'note' => 'Add a video — +15%',
    ],

    'types' => [
        'heading' => 'Notification types',
        'desc' => 'Which events do you want to be notified about?',
        'order_title' => 'New order',
        'order_desc' => 'When your products receive a new order',
        'reviews_title' => 'Reviews',
        'reviews_desc' => 'When a new review or rating arrives',
        'stock_title' => 'Stock alert',
        'stock_desc' => 'When a product drops below 10 items in stock',
        'report_title' => 'Weekly report',
        'report_desc' => 'Sales and view statistics by e-mail',
    ],

    'channels' => [
        'heading' => 'Notification channels',
        'desc' => 'Which channels should notifications be sent through?',
        'email' => 'E-mail',
        'sms' => 'SMS',
        'push' => 'Push',
        'telegram' => 'Telegram',
    ],

    'save' => [
        'unsaved' => 'You have unsaved changes',
        'cancel' => 'Cancel',
        'save' => 'Save',
        'saved' => 'Changes saved.',
    ],
];
