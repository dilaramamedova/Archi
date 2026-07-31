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
    'post_product' => 'Post a product',

    'sd_quick' => 'Quick search',
    'sd_products' => 'Products',
    'sd_masters' => 'Masters',
    'sd_all_results' => 'See all results',

    'catalog' => 'Catalog',
    'specialists' => 'Specialists',
    'blog' => 'Blog',
    'about' => 'About us',
    'b2b' => 'B2B',
    'calculator' => 'Repair calculator',

    'mega_catalog' => [
        ['icon' => 'cat-ic-tikinti.svg', 'title' => 'Construction materials', 'desc' => 'Cement, rebar, brick and other core building materials'],
        ['icon' => 'cat-ic-santexnika.svg', 'title' => 'Plumbing', 'desc' => 'Products for bathroom, kitchen and engineering systems'],
        ['icon' => 'cat-ic-elektrik.svg', 'title' => 'Electrical', 'desc' => 'Cable, switches, sockets and electrical equipment'],
        ['icon' => 'cat-ic-dosheme.svg', 'title' => 'Flooring & cladding', 'desc' => 'Laminate, parquet, tile and porcelain stoneware'],
        ['icon' => 'cat-ic-isiq.svg', 'title' => 'Lighting', 'desc' => 'Lighting solutions for home and commercial spaces'],
        ['icon' => 'cat-ic-dekor.svg', 'title' => 'Decor & furniture', 'desc' => 'Decor and furniture that complete your interior'],
    ],

    'mega_spec' => [
        ['icon' => 'spec-ic-memar.svg', 'title' => 'Architects', 'desc' => 'Modern, functional projects with aesthetic and technical design'],
        ['icon' => 'spec-ic-interyer.svg', 'title' => 'Interior designers', 'desc' => 'Aesthetic and functional organization of space'],
        ['icon' => 'spec-ic-usta.svg', 'title' => 'Masters', 'desc' => 'Tiler, electrician, plumber and other professionals'],
        ['icon' => 'spec-ic-sirket.svg', 'title' => 'Construction companies', 'desc' => 'Professional management of the construction process'],
    ],
    'mega_promo_text' => "Don't know where to start? Get free advice from specialists.",
    'mega_promo_cta' => 'Free consultation',

    'mega_blog' => [
        ['img' => 'mega-blog1.jpg', 'title' => 'Which tile to choose?', 'desc' => "Don't know where to start? Get free advice from specialists.", 'cta' => 'read'],
        ['img' => 'mega-blog2.jpg', 'title' => 'Blog 2', 'desc' => "Don't know where to start? Get free advice from specialists.", 'cta' => 'read'],
        ['img' => 'mega-blog2.jpg', 'title' => 'Blog 3', 'desc' => "Don't know where to start? Get free advice from specialists.", 'cta' => 'pill'],
    ],

    /* --- search autocomplete demo data (replaced by the API once a backend exists) --- */
    'sd_demo_suggests' => [
        'tile 60×60', 'tile adhesive', 'tiler', 'metlakh tile 20×20',
        'marble-effect tile', 'porcelain stoneware', 'plumbing', 'laminate & parquet',
        'facade paint', 'insulation materials', 'electrical cable', 'interior designer',
    ],
    'sd_demo_products' => [
        ['img' => '/assets/fig/1ed736a990f0.jpg', 'name' => 'Ceramic tile 60×60, matte', 'cat' => 'Tiles & metlakh', 'price' => '23.90 ₼'],
        ['img' => '/assets/fig/bca0ec1e.jpg', 'name' => 'Metlakh tile 20×20, patterned', 'cat' => 'Tiles & metlakh', 'price' => '18.50 ₼'],
        ['img' => '/assets/fig/78886edf.jpg', 'name' => 'Marble-effect tile 60×120', 'cat' => 'Tiles & metlakh', 'price' => '49.90 ₼'],
        ['img' => '/assets/fig/50873ec31b52.jpg', 'name' => 'Acrylic facade paint 10 l', 'cat' => 'Paint & enamel', 'price' => '64.00 ₼'],
        ['img' => '/assets/fig/6146d21348a6.jpg', 'name' => 'Stone wool insulation 50 mm', 'cat' => 'Insulation & heating', 'price' => '31.20 ₼'],
        ['img' => '/assets/fig/2701238de96a.jpg', 'name' => 'Laminate flooring 8 mm, oak', 'cat' => 'Laminate & parquet', 'price' => '27.80 ₼'],
    ],
    'sd_demo_masters' => [
        ['initials' => 'RM', 'name' => 'Rashad Mammadov', 'role' => 'Tiler', 'rate' => '4.9'],
        ['initials' => 'TH', 'name' => 'Tural Hasanov', 'role' => 'Tiler', 'rate' => '4.7'],
        ['initials' => 'EQ', 'name' => 'Elchin Guliyev', 'role' => 'Plumber', 'rate' => '4.8'],
        ['initials' => 'NM', 'name' => 'Nigar Mammadova', 'role' => 'Interior designer', 'rate' => '4.9'],
    ],
];
