<?php

// Register page (AZ).

return [
    'title' => 'Qeydiyyatdan keç — ARCHİ',

    'head' => [
        'tag' => 'ARCHİ-yə qoşul',
        'title' => 'Qeydiyyatdan keç',
        'subtitle' => 'Hesab növünü seç — alıcı, satıcı, yoxsa usta/mütəxəssis kimi davam et.',
    ],

    'success' => 'Qeydiyyat uğurla tamamlandı! (demo — backend qoşulmayıb)',

    'roles' => [
        'label' => 'Hesab növü',
        'buyer' => [
            'title' => 'Alıcı',
            'desc' => 'Material al, usta tap, sifariş ver.',
        ],
        'seller' => [
            'title' => 'Satıcı',
            'desc' => 'Məhsullarını yerləşdir və sat.',
        ],
        'master' => [
            'title' => 'Usta / Mütəxəssis',
            'desc' => 'Xidmət göstər, müştəri qazan.',
        ],
    ],

    'form' => [
        'first_name_label' => 'Ad',
        'first_name_placeholder' => 'Adınız',
        'last_name_label' => 'Soyad',
        'last_name_placeholder' => 'Soyadınız',
        'company_label' => 'Şirkət / marka adı',
        'company_placeholder' => 'Məsələn: ARCHI Build MMC',
        'specialization_label' => 'İxtisas',
        'city_label' => 'Şəhər',
        'select_placeholder' => 'Seçin',
        'email_label' => 'E-poçt',
        'email_placeholder' => 'email@example.com',
        'phone_label' => 'Telefon',
        'phone_placeholder' => '+994 50 000 00 00',
        'password_label' => 'Şifrə',
        'password_placeholder' => 'Ən azı 6 simvol',
        'password_confirm_label' => 'Şifrəni təkrarla',
        'password_confirm_placeholder' => 'Şifrəni təkrarla',
        'terms_link' => 'İstifadə şərtləri',
        'terms_and' => 'və',
        'privacy_link' => 'Məxfilik siyasəti',
        'terms_agree' => 'ilə razıyam',
        'submit' => 'Qeydiyyatdan keç',
        'have_account' => 'Artıq hesabın var?',
        'sign_in' => 'Daxil ol',
    ],

    'specializations' => [
        'tiler' => 'Kafelçi',
        'electrician' => 'Elektrik',
        'plumber' => 'Santexnik',
        'painter' => 'Boyaçı',
        'drywaller' => 'Gips-kartonçu',
        'architect' => 'Memar',
        'interior_designer' => 'İnteryer dizayner',
        'other' => 'Digər',
    ],

    'cities' => [
        'baku' => 'Bakı',
        'sumgayit' => 'Sumqayıt',
        'ganja' => 'Gəncə',
        'mingachevir' => 'Mingəçevir',
        'shirvan' => 'Şirvan',
        'other' => 'Digər',
    ],
];
