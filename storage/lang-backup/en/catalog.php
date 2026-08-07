<?php

// Catalog page (EN).

return [
    'title' => 'Catalog — ARCHİ',
    'crumb_current' => 'Catalog',

    'head' => [
        'eyebrow' => 'Catalog',
        'title' => 'Catalog',
        'count' => '860 products',
    ],

    'sort' => [
        'label' => 'Sort:',
        'popular' => 'Most popular',
        'cheap' => 'Cheapest first',
        'expensive' => 'Most expensive first',
        'rating' => 'By rating',
        'newest' => 'Newest additions',
    ],

    // Chip labels are built in catalog.js; :v / :min / :max are placeholders.
    'chips' => [
        'clear' => 'Clear all',
        'size' => ':v cm',
        'surface' => ':v finish',
        'price' => ':min–:max ₼',
        'remove' => 'Remove filter: :v',
    ],

    'empty' => 'No products match the selected filters.',

    'filters' => [
        'categories' => 'Categories',
        'cat_tiles' => 'Wall & floor tiles',
        'cat_paint' => 'Paint & enamel',
        'cat_laminate' => 'Laminate & parquet',
        'cat_plumbing' => 'Plumbing',
        'cat_electric' => 'Electrical & light',
        'cat_insulation' => 'Insulation & heating',

        'price' => 'Price, ₼',
        'price_min_aria' => 'Minimum price',
        'price_max_aria' => 'Maximum price',

        'brand' => 'Brand',
        'brand_marca_corona' => 'Marca Corona',
        'brand_kutahya' => 'Kütahya Seramik',
        'brand_vitra' => 'Vitra',
        'brand_cersanit' => 'Cersanit',

        'surface' => 'Surface',
        'surface_matte' => 'Matte',
        'surface_glossy' => 'Glossy',
        'surface_structured' => 'Structured',

        'size' => 'Size, cm',

        'stock_only' => 'In stock only',
        'apply' => 'Apply filters',
    ],

    'products_cat' => 'Wall & floor tiles',

    // 20 cards, in the same order as the $cards array of the Blade view.
    'products' => [
        ['name' => 'Ceramic tile 60×60, matte', 'reviews' => '(1,876 reviews)'],
        ['name' => 'Marble-effect tile 60×120', 'reviews' => '(640 reviews)'],
        ['name' => 'Ceramic tile 60×60, matte', 'reviews' => '(1,204 reviews)'],
        ['name' => 'Ceramic tile 60×60, matte', 'reviews' => '(980 reviews)'],
        ['name' => 'Mosaic decor 30×30', 'reviews' => '(432 reviews)'],
        ['name' => 'Anti-slip tile R11, 60×60', 'reviews' => '(210 reviews)'],
        ['name' => 'Cement-effect tile 60×60', 'reviews' => '(760 reviews)'],
        ['name' => 'Ceramic tile 60×60, matte', 'reviews' => '(1,510 reviews)'],
        ['name' => 'Terracotta floor tile 15×15', 'reviews' => '(318 reviews)'],
        ['name' => 'Wood-effect tile 20×120', 'reviews' => '(522 reviews)'],
        ['name' => 'Porcelain stoneware 80×80, glossy', 'reviews' => '(1,092 reviews)'],
        ['name' => 'Bathroom tile 25×40', 'reviews' => '(268 reviews)'],
        ['name' => 'Onyx decor panel 60×120', 'reviews' => '(96 reviews)'],
        ['name' => 'Kitchen floor tile 20×20', 'reviews' => '(845 reviews)'],
        ['name' => 'Hexagonal tile 23×27', 'reviews' => '(377 reviews)'],
        ['name' => 'Travertine-effect tile 60×60', 'reviews' => '(1,341 reviews)'],
        ['name' => 'Facade tile 6×24', 'reviews' => '(154 reviews)'],
        ['name' => 'Pool tile 12×24', 'reviews' => '(203 reviews)'],
        ['name' => 'Concrete-effect tile 30×60', 'reviews' => '(689 reviews)'],
        ['name' => 'Zellige tile 10×10', 'reviews' => '(118 reviews)'],
    ],
];
