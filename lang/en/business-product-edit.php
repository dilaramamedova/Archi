<?php

return [
    'title' => 'Product — Business panel',
    'heading_new' => 'Add new product',
    'heading_edit' => 'Edit product',
    'crumbs' => ['panel' => 'Business panel', 'current' => 'Product', 'sep' => '/'],
    'status' => ['published' => 'Draft', 'view_profile' => 'Preview ↗'],
    'progress' => ['label' => 'Profile completeness', 'value' => '85%', 'note' => 'Add a video — +15%'],

    'badge' => [
        'draft' => 'Draft',
        'pending' => 'Pending review',
        'approved' => 'Published',
        'rejected' => 'Rejected',
    ],

    'rejected_note' => 'The product was rejected. Reason:',

    'images' => [
        'title' => 'Product images',
        'desc' => 'At least 1 image is required. The first image is shown as the main image in the catalog.',
        'add' => 'Add image',
        'remove' => 'Remove',
    ],

    'basic' => [
        'title' => 'Basic information',
        'name' => 'Product name',
        'name_placeholder' => 'E.g.: Ceramic tile 60×60, matte',
        'category' => 'Category',
        'select' => 'Select…',
        'brand' => 'Brand',
        'sku' => 'SKU / article no.',
        'sku_placeholder' => 'KFL-6060-MAT-01',
        'barcode' => 'Barcode',
        'barcode_placeholder' => '4780012345678',
    ],

    'pricing' => [
        'title' => 'Price and stock',
        'price' => 'Sale price',
        'old_price' => 'Price before discount',
        'old_price_placeholder' => '29.90',
        'unit' => 'Unit',
        'stock' => 'Quantity in stock',
        'min_order' => 'Minimum order',
        'min_order_placeholder' => '1',
        'shelf' => 'Warehouse shelf',
        'shelf_placeholder' => 'A-3',
        'units' => [
            'piece' => 'Piece',
            'm2' => 'm²',
            'lm' => 'Linear meter',
            'kg' => 'Kg',
            'litre' => 'Litre',
            'set' => 'Set',
            'roll' => 'Roll',
            'pack' => 'Pack',
        ],
    ],

    'description' => [
        'title' => 'Description and specifications',
        'short' => 'Short description',
        'short_placeholder' => 'Describe size, material, where it is used and its advantages…',
        'dimensions' => 'Dimensions',
        'dimensions_placeholder' => '60 × 60 cm',
        'material' => 'Material',
        'material_placeholder' => 'Porcelain stoneware',
        'color' => 'Color',
        'color_placeholder' => 'White / marble veining',
        'country' => 'Country of origin',
        'country_placeholder' => 'Italy',
    ],

    'save' => [
        'note' => 'The product appears in the catalog and search after admin approval',
        'draft' => 'Save draft',
        'publish' => 'Publish',
        'error_name' => 'Product name and category are required',
        'error_image' => 'Add at least 1 image',
    ],
];
