<?php

// Quick calculator page (AZ).

return [
    'title' => 'Sürətli təmir kalkulyatoru — ARCHİ',
    'breadcrumb' => 'Sürətli kalkulyator',

    'head' => [
        'tag' => 'Sürətli kalkulyator',
        'title' => 'Təmirin təxmini dəyəri',
        'subtitle' => 'Bir neçə parametr — 3 büdcə variantı.',
    ],

    'object' => [
        'label' => 'Obyektin növü',
        'apartment' => 'Mənzil',
        'house' => 'Fərdi ev',
        'office' => 'Ofis',
        'commercial' => 'Kommersiya',
    ],

    'area' => [
        'label' => 'Obyektin sahəsi',
        'unit' => 'm²',
    ],

    'type' => [
        'label' => 'Təmir növü',
        'shell' => 'Qara',
        'cosmetic' => 'Kosmetik',
        'major' => 'Əsaslı',
        'turnkey' => 'Açar təslim',
    ],

    'rooms' => [
        'label' => 'Otaqların sayı',
        'studio' => 'Studio',
        'one' => '1',
        'two' => '2',
        'three' => '3',
        'four_plus' => '4+',
    ],

    'level' => [
        'label' => 'Material səviyyəsi',
        'economy' => 'Ekonom',
        'standard' => 'Standart',
        'premium' => 'Premium',
    ],

    'result' => [
        'label' => ':area m² üçün təxmini dəyər',
        'currency' => 'AZN-dən',
        'recommended' => 'Tövsiyə',
    ],

    'actions' => [
        'next' => 'Ətraflı hesablamanı əldə et  →',
        'pdf' => 'PDF yüklə',
        'whatsapp' => 'WhatsApp-a göndər',
        'save' => 'Yadda saxla',
    ],

    'messages' => [
        'saved' => 'Hesablama yadda saxlanıldı.',
        'whatsapp' => 'ARCHİ təmir hesablaması: :area m² · :level · ~:price AZN',
    ],
];
