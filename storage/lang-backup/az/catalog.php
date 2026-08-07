<?php

// Catalog page (AZ) — values are the exact strings from the old catalog.html.

return [
    'title' => 'Kataloq — ARCHİ',
    'crumb_current' => 'Kataloq',

    'head' => [
        'eyebrow' => 'Kataloq',
        'title' => 'Kataloq',
        'count' => '860 məhsul',
    ],

    'sort' => [
        'label' => 'Sırala:',
        'popular' => 'Ən populyar',
        'cheap' => 'Əvvəlcə ucuz',
        'expensive' => 'Əvvəlcə bahalı',
        'rating' => 'Reytinqə görə',
        'newest' => 'Yeni əlavələr',
    ],

    // Chip labels are built in catalog.js; :v / :min / :max are placeholders.
    'chips' => [
        'clear' => 'Hamısını təmizlə',
        'size' => ':v sm',
        'surface' => ':v səth',
        'price' => ':min–:max ₼',
        'remove' => 'Filtri sil: :v',
    ],

    'empty' => 'Seçilmiş filtrlərə uyğun məhsul tapılmadı.',

    'filters' => [
        'categories' => 'Kateqoriyalar',
        'cat_tiles' => 'Kafel & metlax',
        'cat_paint' => 'Boya & emal',
        'cat_laminate' => 'Laminant & parket',
        'cat_plumbing' => 'Santexnika',
        'cat_electric' => 'Elektrik & işıq',
        'cat_insulation' => 'İzolyasiya & istilik',

        'price' => 'Qiymət, ₼',
        'price_min_aria' => 'Minimum qiymət',
        'price_max_aria' => 'Maksimum qiymət',

        'brand' => 'Brend',
        'brand_marca_corona' => 'Marca Corona',
        'brand_kutahya' => 'Kütahya Seramik',
        'brand_vitra' => 'Vitra',
        'brand_cersanit' => 'Cersanit',

        'surface' => 'Səth',
        'surface_matte' => 'Mat',
        'surface_glossy' => 'Parlaq',
        'surface_structured' => 'Struktur',

        'size' => 'Ölçü, sm',

        'stock_only' => 'Yalnız stokda olanlar',
        'apply' => 'Filtrləri tətbiq et',
    ],

    'products_cat' => 'Kafel & metlax',

    // 20 cards, in the same order as the $cards array of the Blade view.
    'products' => [
        ['name' => 'Keramik kafel 60×60, mat', 'reviews' => '(1,876 rəy)'],
        ['name' => 'Mərmər effektli kafel 60×120', 'reviews' => '(640 rəy)'],
        ['name' => 'Keramik kafel 60×60, mat', 'reviews' => '(1,204 rəy)'],
        ['name' => 'Keramik kafel 60×60, mat', 'reviews' => '(980 rəy)'],
        ['name' => 'Mozaika dekor 30×30', 'reviews' => '(432 rəy)'],
        ['name' => 'Anti-slip kafel R11, 60×60', 'reviews' => '(210 rəy)'],
        ['name' => 'Sement effektli kafel 60×60', 'reviews' => '(760 rəy)'],
        ['name' => 'Keramik kafel 60×60, mat', 'reviews' => '(1,510 rəy)'],
        ['name' => 'Terrakota metlax 15×15', 'reviews' => '(318 rəy)'],
        ['name' => 'Ağac effektli kafel 20×120', 'reviews' => '(522 rəy)'],
        ['name' => 'Keramoqranit 80×80, parlaq', 'reviews' => '(1,092 rəy)'],
        ['name' => 'Hamam kafeli 25×40', 'reviews' => '(268 rəy)'],
        ['name' => 'Onyx dekor panel 60×120', 'reviews' => '(96 rəy)'],
        ['name' => 'Mətbəx metlaxı 20×20', 'reviews' => '(845 rəy)'],
        ['name' => 'Şestiguşəli kafel 23×27', 'reviews' => '(377 rəy)'],
        ['name' => 'Travertin effektli kafel 60×60', 'reviews' => '(1,341 rəy)'],
        ['name' => 'Fasad kafeli 6×24', 'reviews' => '(154 rəy)'],
        ['name' => 'Bassein kafeli 12×24', 'reviews' => '(203 rəy)'],
        ['name' => 'Beton effektli kafel 30×60', 'reviews' => '(689 rəy)'],
        ['name' => 'Zellige kafel 10×10', 'reviews' => '(118 rəy)'],
    ],
];
