<?php

return [
    'title' => 'Warehouse & stock — Business panel',
    'heading' => 'Warehouse & stock',
    'crumbs' => ['panel' => 'Business panel', 'current' => 'Warehouse & stock', 'sep' => '/'],
    'status' => ['published' => 'Active', 'view_profile' => 'Upload via Excel ↗'],
    'progress' => ['label' => 'Profile completeness', 'value' => '85%', 'note' => 'Add a video — +15%'],

    'kpi' => [
        'total' => 'Total products',
        'low' => 'Running low',
        'out' => 'Out of stock',
        'value' => 'Warehouse value',
    ],

    'filters' => [
        'all' => 'All',
        'low' => 'Low stock',
        'out' => 'Out of stock',
        'unpublished' => 'Unpublished',
        'search_placeholder' => 'Product name or SKU',
    ],

    'table' => [
        'product' => 'Product',
        'shelf' => 'Shelf',
        'stock' => 'In stock',
        'status' => 'Status',
        'status_ok' => 'Sufficient',
        'status_low' => 'Running low',
        'status_out' => 'Out of stock',
        'add_stock' => 'Add stock',
        'add_stock_title' => 'Update stock',
        'add_stock_label' => 'New stock quantity',
    ],

    'empty' => [
        'title' => 'No products in the warehouse',
        'desc' => 'Once you add products, their stock status will appear here.',
    ],
];

