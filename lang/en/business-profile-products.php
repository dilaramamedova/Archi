<?php

// Business cabinet — products (EN).

return [
    'title' => 'Products — ARCHİ',

    'crumbs' => [
        'panel' => 'Business panel',
        'separator' => '/',
        'current' => 'Profile editing',
    ],
    'heading' => 'Editing the business profile',
    'status' => 'Published',
    'view_profile' => 'View profile ↗',

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
        'hint' => 'Add a video — +15%',
    ],

    'list' => [
        'title' => 'Products (1,240)',
        'summary' => '1,180 active · 42 running low · 18 hidden',
        'add' => '＋ Add product',
        'edit' => 'Edit',
        'toggle' => 'Product visibility',
        'empty' => 'No matching products found',
    ],
    'filters' => [
        'search' => 'Search a product...',
        'category' => 'Category',
        'cat_all' => 'All categories',
        'cat_tile' => 'Tiles & metlakh',
        'status' => 'Status',
        'status_all' => 'All statuses',
        'status_active' => 'Active',
        'status_low' => 'Running low',
        'status_hidden' => 'Hidden',
    ],

    'products' => [
        'tile_matte' => [
            'name' => 'Ceramic tile 60×60, matte',
            'cat' => 'Tiles & metlakh',
            'price' => '23.90 ₼',
            'stock' => '480 m²',
        ],
        'tile_marble' => [
            'name' => 'Marble-effect tile 60×120',
            'cat' => 'Tiles & metlakh',
            'price' => '49.90 ₼',
            'stock' => '120 m²',
        ],
        'tile_metlakh' => [
            'name' => 'Metlakh tile 20×20, patterned',
            'cat' => 'Tiles & metlakh',
            'price' => '18.50 ₼',
            'stock' => '8 m² · running low',
        ],
        'tile_mosaic' => [
            'name' => 'Mosaic decor 30×30',
            'cat' => 'Tiles & metlakh',
            'price' => '12.90 ₼',
            'stock' => '0 · out of stock',
        ],
    ],

    'pager' => [
        'label' => 'Pagination',
        'prev_label' => 'Previous page',
        'next_label' => 'Next page',
        'prev' => '←',
        'page1' => '1',
        'page2' => '2',
        'page3' => '3',
        'gap' => '…',
        'last' => '310',
        'next' => '→',
    ],

    'save' => [
        'unsaved' => 'You have unsaved changes',
        'cancel' => 'Cancel',
        'save' => 'Save',
        'saved' => 'Changes saved.',
    ],
];
