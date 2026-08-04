<?php

// Login page + shared login modal (AZ).

return [
    'title' => 'Daxil ol — ARCHİ',

    'head' => [
        'tag' => 'Xoş gəlmisən',
        'title' => 'Daxil ol',
        'subtitle' => 'Hesabına gir — alıcı, satıcı və ya usta kabinetinə davam et.',
    ],

    'success' => 'Giriş uğurlu oldu!',

    'form' => [
        'identifier_label' => 'E-poçt və ya telefon',
        'identifier_placeholder' => 'email@example.com',
        'password_label' => 'Şifrə',
        'password_placeholder' => 'Şifrə',
        'remember' => 'Məni xatırla',
        'forgot' => 'Şifrəni unutmusan?',
        'submit' => 'Daxil ol',
        'no_account' => 'Hesabın yoxdur?',
        'sign_up' => 'Qeydiyyatdan keç',
    ],

    'modal' => [
        'close' => 'Bağla',
    ],

    'errors' => [
        'invalid_credentials' => 'E-poçt/telefon və ya şifrə yanlışdır.',
        'pending_approval' => 'Hesabınız hələ admin tərəfindən təsdiqlənməyib. Zəhmət olmasa gözləyin.',
        'rejected' => 'Hesabınız rədd edilib. Əlavə məlumat üçün bizimlə əlaqə saxlayın.',
        'blocked' => 'Hesabınız bloklanıb. Əlavə məlumat üçün bizimlə əlaqə saxlayın.',
        'not_active' => 'Hesabınız aktiv deyil.',
    ],
];
