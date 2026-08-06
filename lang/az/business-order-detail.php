<?php

return [
    'title' => 'Sifariş detalları — Biznes paneli',
    'crumbs' => ['panel' => 'Biznes paneli', 'current' => 'Sifariş', 'sep' => '/'],
    'status' => ['published' => 'Sifariş', 'view_profile' => 'Qaimə ↗'],
    'progress' => ['label' => 'Profil tamlığı', 'value' => '85%', 'note' => 'Video əlavə et — +15%'],

    'stepper' => [
        'title' => 'İcra mərhələsi',
        'received_at' => 'Sifariş :date tarixində daxil oldu',
        'step_pending' => 'Qəbul edildi',
        'step_processing' => 'Yığılır',
        'step_shipped' => 'Kuryerdə',
        'step_delivered' => 'Çatdırıldı',
        'cancelled' => 'Sifariş ləğv edilib',
    ],

    'customer' => [
        'title' => 'Müştəri',
        'name' => 'Ad',
        'phone' => 'Telefon',
        'email' => 'E-poçt',
        'prev_orders' => 'Əvvəlki sifarişlər',
        'orders_count' => 'sifariş',
    ],

    'delivery' => [
        'title' => 'Çatdırılma',
        'address' => 'Ünvan',
        'note' => 'Qeyd',
        'method' => 'Üsul',
        'method_value' => 'Standart · 3–5 iş günü',
        'city' => 'Şəhər',
    ],

    'items' => [
        'title' => 'Yığılmalı məhsullar',
        'qty' => 'əd',
        'stock_left' => 'qalıq :count əd',
        'stock_low' => 'qalıq :count əd — azalır',
        'subtotal' => 'Ara cəm',
        'discount' => 'Endirim (:code) — platforma hesabına',
        'commission' => 'Komissiya (8%)',
        'payout' => 'Sənə ödəniləcək',
    ],

    'actions' => [
        'hint' => 'Yığım tamamlananda statusu dəyiş — müştəriyə avtomatik bildiriş gedir.',
        'cancel' => 'Sifarişi ləğv et',
        'print' => 'Qaiməni çap et',
        'to_processing' => 'Yığılmağa götür',
        'to_shipped' => 'Yığıldı — kuryerə ver',
        'to_delivered' => 'Çatdırıldı kimi qeyd et',
        'cancel_confirm_title' => 'Sifarişi ləğv et',
        'cancel_confirm_body' => 'Bu sifarişi ləğv etmək istədiyinizə əminsiniz? Müştəriyə bildiriş göndəriləcək.',
    ],
];
