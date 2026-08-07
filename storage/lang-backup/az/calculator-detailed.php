<?php

return [
    'title' => 'Ətraflı təmir kalkulyatoru — ARCHİ',

    'head' => [
        'tag' => 'Ətraflı kalkulyator',
        'title' => 'Dəqiq təmir smetası hazırlayın',
        'sub' => 'Otaqları, işləri və materialları seçin — səbətdəki məhsullar da hesablamaya daxil edilir.',
    ],

    'money' => ':amount ₼',
    'approx' => '≈ :amount',
    'currency' => '₼',
    'currency_code' => 'AZN',

    'steps' => [
        'object' => 'Obyekt',
        'rooms' => 'Otaqlar',
        'works' => 'İşlər',
        'materials' => 'Materiallar',
        'cart' => 'Səbət',
        'summary' => 'Yekun',
    ],

    'units' => [
        'm2' => 'm²',
        'm' => 'm',
        'point' => 'nöqtə',
        'pc' => 'əd',
    ],

    'object' => [
        'k' => 'Addım 1 · Obyekt',
        'h' => 'Obyekt haqqında məlumat',
        'type' => 'Obyektin növü',
        'types' => [
            'apartment' => 'Mənzil',
            'house' => 'Fərdi ev',
            'office' => 'Ofis',
            'commercial' => 'Kommersiya',
        ],
        'area' => 'Sahə',
        'city' => 'Şəhər',
        'floor' => 'Mərtəbə',
        'property' => 'Daşınmaz əmlakın növü',
        'properties' => [
            'new' => 'Yeni tikili',
            'used' => 'İkinci əl',
        ],
        'condition' => 'Obyektin vəziyyəti',
        'conditions' => [
            'raw' => 'Təmirsiz',
            'after_demolition' => 'Demontajdan sonra',
            'rough_done' => 'Hazır qara təmir',
        ],
        'extra' => 'Əlavə parametrlər',
        'design' => 'Dizayn layihəsi var',
        'drawings' => 'Çertyojlar var',
        'demolition' => 'Demontaj lazımdır',
        'default_city' => 'Bakı',
        'default_floor' => '7 / 16',
    ],

    'rooms' => [
        'k' => 'Addım 2 · Otaqlar',
        'h' => 'Otaqları əlavə edin',
        'd' => 'Hər otaq üçün ölçüləri və üzlük növünü seçin.',
        'remove' => 'Sil',
        'floor_area' => 'Döşəmə sahəsi',
        'height' => 'Tavan hünd.',
        'perimeter' => 'Divar perimetri',
        'doors' => 'Qapı',
        'windows' => 'Pəncərə',
        'floor_cover' => 'Döşəmə örtüyü',
        'wall_cover' => 'Divar üzlüyü',
        'heating' => 'İsti döşəmə',
        'edit' => 'Redaktə et',
        'add' => '+  Otaq əlavə et',
        'floors' => [
            'laminate' => 'Laminat',
            'parquet' => 'Parket',
            'spc' => 'SPC',
            'tile' => 'Kafel',
        ],
        'walls' => [
            'paint' => 'Boya',
            'wallpaper' => 'Divar kağızı',
            'plaster' => 'Dekor suvaq',
            'tile' => 'Kafel',
        ],
        'defaults' => [
            'living' => 'Qonaq otağı',
            'kitchen' => 'Mətbəx',
            'bathroom' => 'Hamam otağı',
            'new' => 'Yeni otaq',
        ],
    ],

    'works' => [
        'k' => 'Addım 3 · İşlər',
        'h' => 'Lazım olan işləri seçin',
        'd' => 'Yalnız ehtiyacınız olan işləri işarələyin — qiymət avtomatik hesablanır.',
        'price_unit' => ':price ₼/:unit',
        'selected' => 'Seçilmiş işlər (:count)',
        'items' => [
            'demolition' => 'Demontaj',
            'wall_leveling' => 'Divarların hamarlanması',
            'plaster' => 'Suvaq',
            'putty' => 'Şpaklyovka',
            'screed' => 'Döşəmə styaşkası',
            'waterproofing' => 'Hidroizolyasiya',
            'soundproofing' => 'Səs izolyasiyası',
            'electrical' => 'Elektrik işləri',
            'plumbing' => 'Santexnika',
            'ventilation' => 'Ventilyasiya',
            'air_conditioning' => 'Kondisioner sistemi',
            'tile_prep' => 'Kafel üçün hazırlıq',
            'paint_prep' => 'Boya üçün hazırlıq',
        ],
    ],

    'materials' => [
        'k' => 'Addım 4 · Materiallar',
        'h' => 'Üzlük materiallarını seçin',
        'd' => 'Səbətdəki məhsullar avtomatik daxil edilir, qalanları kataloqdan seçin.',
        'banner' => 'Səbətinizdəki :count məhsul hesablamaya avtomatik daxil edildi',
        'not_selected' => 'Seçilməyib',
        'pick' => 'Kataloqdan seç',
        'from_cart' => 'Səbətdən',
        'labels' => [
            'floor' => 'Döşəmə örtüyü',
            'tile' => 'Kafel & metlax',
            'paint' => 'Boya',
            'doors' => 'Qapılar',
            'plumbing' => 'Santexnika',
            'lighting' => 'İşıqlandırma',
        ],
    ],

    'cats' => [
        'floor' => 'Döşəmə',
        'tile' => 'Kafel & metlax',
        'paint' => 'Boya',
        'doors' => 'Qapılar',
        'plumbing' => 'Santexnika',
    ],

    'cart' => [
        'k' => 'Addım 5 · Səbət',
        'h' => 'Səbətdəki məhsullar hesablamada',
        'd' => 'Miqdar sahə və ehtiyat faizinə görə avtomatik hesablanır. İstəmədiyinizi çıxarın.',
        'summary' => ':count məhsul seçildi · hesablamaya daxil',
        'stock' => [
            'in' => 'Stokda var',
            'order' => 'Sifarişlə 3-5 gün',
        ],
        'items' => [
            'laminate' => [
                'name' => 'Quick-Step laminat, 32-ci sinif',
                'calc' => '80 m² + 10% = 88 m² → 22 paket',
            ],
            'tile' => [
                'name' => 'Metlax kafel 20×20',
                'calc' => '32 m² + 15% = 37 m² → 26 qutu',
            ],
            'paint' => [
                'name' => 'Caparol mat divar boyası',
                'calc' => '2 qat · 120 m² → 12 vedrə',
            ],
            'door' => [
                'name' => 'MDF qapı, ağ',
                'calc' => '5 otaq → 5 ədəd',
            ],
            'toilet' => [
                'name' => 'Asma unitaz, komplekt',
                'calc' => '2 sanitar → 2 ədəd',
            ],
            'mixer' => [
                'name' => 'Qarışdırıcı, xrom',
                'calc' => '3 nöqtə → 3 ədəd',
            ],
        ],
    ],

    'summary' => [
        'k' => 'Addım 6 · Yekun smeta',
        'h' => ':area m² üçün təmir smetası',
        'd' => 'Bütün materiallar, işlər və xidmətlər üzrə dəqiq hesablama.',
        'total' => 'Yekun məbləğ',
        'rows' => [
            'rough' => 'Qara təmir materialları',
            'finish' => 'Üzlük materialları',
            'plumbing' => 'Santexnika',
            'electrical' => 'Elektrik',
            'lighting' => 'İşıqlandırma',
            'doors' => 'Qapılar',
            'works' => 'İşlər',
            'delivery' => 'Çatdırılma və qaldırılma',
            'services' => 'Əlavə xidmətlər',
            'reserve' => '10% ehtiyat',
        ],
    ],

    'side' => [
        'label' => 'Cari smeta',
        'meta' => ':area m² · :rooms otaq',
        'works' => 'İşlər',
        'cart_materials' => 'Səbətdəki materiallar',
        'reserve' => 'Ehtiyat (10%)',
        'cart_banner' => 'Səbətinizdə :count məhsul var',
        'use_in_calc' => 'Hesablamada istifadə',
        'back' => 'Geri',
        'next' => 'Növbəti addım',
        'calculate' => 'Smetanı hesabla',
    ],

    'tips' => [
        'tile' => [
            't1' => 'Premium kafeli standart ilə əvəz etməklə',
            't2' => '8 400 ₼ qənaət',
        ],
        'doors' => [
            't1' => 'Orta qiymətli qapılar seçməklə',
            't2' => 'büdcəni 12% azaldın',
        ],
        'top' => [
            't1' => 'Ən bahalı kateqoriya',
            't2' => 'üzlük materiallarıdır',
        ],
    ],

    'actions' => [
        'add_all' => 'Bütün materialları səbətə at',
        'pdf' => 'PDF',
        'whatsapp' => 'WhatsApp',
        'save' => 'Yadda saxla',
        'turnkey' => 'Açar təslim təmir sifariş et',
    ],

    'alerts' => [
        'added' => 'Bütün materiallar səbətə əlavə olundu.',
        'saved' => 'Smeta yadda saxlanıldı.',
        'turnkey' => 'Açar təslim sifariş göndərildi. Menecer sizinlə əlaqə saxlayacaq.',
        'whatsapp' => 'ARCHİ smeta: :total AZN',
    ],

    // Regex alternatives that mark a room as a bathroom (case-insensitive).
    'bathroom_pattern' => 'hamam|sanitar',
];
