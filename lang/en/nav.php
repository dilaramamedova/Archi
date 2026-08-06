<?php

// Shared navbar strings (EN).

return [
    'logo_aria' => 'ARCHİ — home page',
    'search_placeholder' => 'Search products, brands or specialists',
    'search_aria' => 'Search',
    'lang_aria' => 'Language selection',
    'favorites' => 'Favorites',
    'cart' => 'Cart',
    'sign_in' => 'Sign in',
    'register' => 'Sign up',
    'logout' => 'Sign out',
    'post_product' => 'Post a product',
    'notifications' => 'Notifications',
    'mark_all_read' => 'Mark all as read',
    'no_notifications' => 'No notifications yet',
    'all_notifications' => 'View all notifications',
    'my_orders' => 'My orders',
    'messages' => 'Messages',
    'account_settings' => 'Account settings',
    'help_center' => 'Help center',

    'sd_quick' => 'Quick search',
    'sd_products' => 'Products',
    'sd_masters' => 'Pros',
    'sd_all_results' => 'See all results',
    'sd_loading' => 'Loading…',
    'sd_no_results' => 'No results found',

    'catalog' => 'Catalog',
    'specialists' => 'Specialists',
    'blog' => 'Blog',
    'about' => 'About us',
    'b2b' => 'B2B',
    'calculator' => 'Repair calculator',

    /* icons are zipped in by index in components/navbar.blade.php */
    'mega_catalog' => [
        ['title' => 'Construction materials', 'desc' => 'Cement, rebar, brick and other core building materials'],
        ['title' => 'Plumbing', 'desc' => 'Products for bathroom, kitchen and engineering systems'],
        ['title' => 'Electrical', 'desc' => 'Cable, switches, sockets and electrical equipment'],
        ['title' => 'Flooring & cladding', 'desc' => 'Laminate, parquet, tile and porcelain stoneware'],
        ['title' => 'Lighting', 'desc' => 'Lighting solutions for home and commercial spaces'],
        ['title' => 'Decor & furniture', 'desc' => 'Decor and furniture that complete your interior'],
    ],

    'mega_spec' => [
        ['title' => 'Architects', 'desc' => 'Modern, functional projects with aesthetic and technical design'],
        ['title' => 'Interior designers', 'desc' => 'Aesthetic and functional organization of space'],
        ['title' => 'Pros', 'desc' => 'Tiler, electrician, plumber and other professionals'],
        ['title' => 'Construction companies', 'desc' => 'Professional management of the construction process'],
    ],
    'mega_promo_text' => "Don't know where to start? Get free advice from specialists.",
    'mega_promo_cta' => 'Free consultation',

    'mega_blog' => [
        ['title' => 'Which tile to choose?', 'desc' => "Don't know where to start? Get free advice from specialists."],
        ['title' => 'Blog 2', 'desc' => "Don't know where to start? Get free advice from specialists."],
        ['title' => 'Blog 3', 'desc' => "Don't know where to start? Get free advice from specialists."],
    ],

    /* --- search autocomplete demo data (replaced by the API once a backend exists) --- */
    'sd_demo_suggests' => [
        'tile 60×60', 'tile adhesive', 'tiler', 'floor tile 20×20',
        'marble-effect tile', 'porcelain stoneware', 'plumbing', 'laminate & parquet',
        'facade paint', 'insulation materials', 'electrical cable', 'interior designer',
    ],
    'sd_demo_products' => [
        ['name' => 'Ceramic tile 60×60, matte', 'cat' => 'Wall & floor tiles', 'price' => '23.90 ₼'],
        ['name' => 'Floor tile 20×20, patterned', 'cat' => 'Wall & floor tiles', 'price' => '18.50 ₼'],
        ['name' => 'Marble-effect tile 60×120', 'cat' => 'Wall & floor tiles', 'price' => '49.90 ₼'],
        ['name' => 'Acrylic facade paint 10 l', 'cat' => 'Paint & enamel', 'price' => '64.00 ₼'],
        ['name' => 'Stone wool insulation 50 mm', 'cat' => 'Insulation & heating', 'price' => '31.20 ₼'],
        ['name' => 'Laminate flooring 8 mm, oak', 'cat' => 'Laminate & parquet', 'price' => '27.80 ₼'],
    ],
    'sd_demo_masters' => [
        ['initials' => 'RM', 'name' => 'Rashad Mammadov', 'role' => 'Wall & floor tiler', 'rate' => '4.9'],
        ['initials' => 'TH', 'name' => 'Tural Hasanov', 'role' => 'Wall & floor tiler', 'rate' => '4.7'],
        ['initials' => 'EQ', 'name' => 'Elchin Guliyev', 'role' => 'Plumber', 'rate' => '4.8'],
        ['initials' => 'NM', 'name' => 'Nigar Mammadova', 'role' => 'Interior designer', 'rate' => '4.9'],
    ],
];
