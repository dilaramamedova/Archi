<?php

// Shared navbar strings. AZ values are the exact text of the old project — do not
// reword them, pixel parity depends on it.

return [
    /* --- row 1 --- */
    'logo_aria' => 'ARCHİ — ana səhifə',
    'search_placeholder' => 'Məhsul, marka və ya mütəxəssis axtarın',
    'search_aria' => 'Axtarış',
    'lang_aria' => 'Dil seçimi',
    'favorites' => 'Sevimlilər',
    'cart' => 'Səbət',
    'sign_in' => 'Daxil ol',
    'logout' => 'Çıxış',
    'post_product' => 'Məhsul yerləşdir',

    /* --- search dropdown (built in JS, labels passed through data-* attributes) --- */
    'sd_quick' => 'Sürətli axtarış',
    'sd_products' => 'Məhsullar',
    'sd_masters' => 'Ustalar',
    'sd_all_results' => 'Bütün nəticələrə bax',
    'sd_loading' => 'Yüklənir…',
    'sd_no_results' => 'Nəticə tapılmadı',

    /* --- row 2 --- */
    'catalog' => 'Kataloq',
    'specialists' => 'Mütəxəssislər',
    'blog' => 'Bloq',
    'about' => 'Haqqımızda',
    'b2b' => 'B2B',
    'calculator' => 'Təmir kalkulyatoru',

    /* --- mega menu: catalog (icons are zipped in by index in components/navbar.blade.php) --- */
    'mega_catalog' => [
        ['title' => 'Tikinti materialları', 'desc' => 'Sement, armatur, kərpic və digər əsas tikinti məhsulları'],
        ['title' => 'Santexnika', 'desc' => 'Hamam, mətbəx və mühəndislik sistemləri üçün məhsullar'],
        ['title' => 'Elektrik', 'desc' => 'Kabel, açar, rozetka və elektrik avadanlıqları'],
        ['title' => 'Döşəmə və üzlük', 'desc' => 'Laminat, parket, kafel və keramoqranit məhsulları'],
        ['title' => 'İşıqlandırma', 'desc' => 'Ev və kommersiya məkanları üçün işıqlandırma həlləri'],
        ['title' => 'Dekor və mebel', 'desc' => 'İnteryerinizi tamamlayan dekor və mebel həlləri'],
    ],

    /* --- mega menu: specialists --- */
    'mega_spec' => [
        ['title' => 'Memarlar', 'desc' => 'Müasir və funksional layihələrin hazırlanması, estetik və texniki dizayn'],
        ['title' => 'İnteryer dizaynerlər', 'desc' => 'Məkanın estetik və funksional təşkili'],
        ['title' => 'Ustalar', 'desc' => 'Kafelçi, elektrik, santexnik və digər peşəkarlar'],
        ['title' => 'Tikinti şirkətləri', 'desc' => 'Tikinti prosesinin peşəkar idarə olunması'],
    ],
    'mega_promo_text' => 'Haradan başlayacağını bilmirsən? Mütəxəssislərdən pulsuz məsləhət al.',
    'mega_promo_cta' => 'Pulsuz konsultasiya',

    /* --- mega menu: blog --- */
    'mega_blog' => [
        ['title' => 'Hansı kafeli seçək?', 'desc' => 'Haradan başlayacağını bilmirsən? Mütəxəssislərdən pulsuz məsləhət al.'],
        ['title' => 'Blog 2', 'desc' => 'Haradan başlayacağını bilmirsən? Mütəxəssislərdən pulsuz məsləhət al.'],
        ['title' => 'Blog 3', 'desc' => 'Haradan başlayacağını bilmirsən? Mütəxəssislərdən pulsuz məsləhət al.'],
    ],

    /* --- search autocomplete demo data (replaced by the API once a backend exists) --- */
    'sd_demo_suggests' => [
        'kafel 60×60', 'kafel yapışdırıcısı', 'kafel ustası', 'metlax kafel 20×20',
        'mərmər effektli kafel', 'keramoqranit', 'santexnika', 'laminat & parket',
        'fasad boyası', 'izolyasiya materialları', 'elektrik kabeli', 'interyer dizayneri',
    ],
    'sd_demo_products' => [
        ['name' => 'Keramik kafel 60×60, mat', 'cat' => 'Kafel & metlax', 'price' => '23.90 ₼'],
        ['name' => 'Metlax kafel 20×20, naxışlı', 'cat' => 'Kafel & metlax', 'price' => '18.50 ₼'],
        ['name' => 'Mərmər effektli kafel 60×120', 'cat' => 'Kafel & metlax', 'price' => '49.90 ₼'],
        ['name' => 'Akril fasad boyası 10 l', 'cat' => 'Boya & emal', 'price' => '64.00 ₼'],
        ['name' => 'Daş yunu izolyasiya 50 mm', 'cat' => 'İzolyasiya & istilik', 'price' => '31.20 ₼'],
        ['name' => 'Laminat parket 8 mm, palıd', 'cat' => 'Laminant & parket', 'price' => '27.80 ₼'],
    ],
    'sd_demo_masters' => [
        ['initials' => 'RM', 'name' => 'Rəşad Məmmədov', 'role' => 'Kafel & metlax ustası', 'rate' => '4.9'],
        ['initials' => 'TH', 'name' => 'Tural Həsənov', 'role' => 'Kafel & metlax ustası', 'rate' => '4.7'],
        ['initials' => 'EQ', 'name' => 'Elçin Quliyev', 'role' => 'Santexnik', 'rate' => '4.8'],
        ['initials' => 'NM', 'name' => 'Nigar Məmmədova', 'role' => 'İnteryer dizayneri', 'rate' => '4.9'],
    ],
];
