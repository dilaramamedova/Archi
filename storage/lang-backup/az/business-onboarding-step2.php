<?php

// Business onboarding step 2 — contact details (AZ).
// The Figma frame's h1 still reads "Şirkət məlumatları" (copied from step 1 in the
// design) while the stepper marks step 2 "Əlaqə" as active — kept verbatim.

return [
    'title' => 'biznes — Tamamlama · Addım 2: Şirkət məlumatları',

    'back' => '←  Mağaza panelinə qayıt',
    'heading' => 'Şirkət məlumatları',

    'steps' => [
        'basic' => 'Əsas məlumat',
        'contact' => 'Əlaqə',
        'product' => 'İlk məhsul',
    ],

    'form' => [
        'contact_person_label' => 'Əlaqələndirici şəxs',
        'contact_person_placeholder' => 'Ad Soyad',
        'role_label' => 'Vəzifə',
        'role_placeholder' => 'Satış meneceri',
        'phone_label' => 'Telefon',
        'phone_placeholder' => '+994 50 000 00 00',
        'whatsapp_label' => 'WhatsApp',
        'whatsapp_placeholder' => '+994 50 000 00 00',
        'telegram_label' => 'Telegram',
        'telegram_placeholder' => '@istifadeci',
        'email_label' => 'E-poçt',
        'email_placeholder' => 'info@example.com',
        'website_label' => 'Veb-sayt',
        'website_placeholder' => 'www.example.com',
        'hours_label' => 'İş saatları',
        'hours_placeholder' => 'B.e–Cümə 09:00–18:00',
        'instagram_label' => 'Instagram',
        'instagram_placeholder' => '@brand',
        'linkedin_label' => 'LinkedIn',
        'linkedin_placeholder' => '/company/brand',
        'facebook_label' => 'Facebook',
        'facebook_placeholder' => '/brand',
        'languages_label' => 'Ünsiyyət dilləri',
    ],

    'languages' => [
        'az' => 'Azərbaycan',
        'ru' => 'Rus',
        'en' => 'İngilis',
        'tr' => 'Türk',
        'other' => 'Digər',
    ],

    'actions' => [
        'save' => 'Yadda saxla və davam et  →',
        'later' => 'Sonra davam edərəm',
    ],

    'side' => [
        'progress_title' => 'Tamamlanma: 25% → 50%',
        'progress_note' => 'Bu addımı bitirəndə mağazan 50% hazır olacaq.',
        'tip_title' => '💡 Məsləhət',
        'tip_text' => 'VÖEN-i dəqiq yaz — sənədlər yoxlanarkən uyğunsuzluq ən çox gecikmə yaradan səbəbdir. Marka adı sonradan dəyişdirilə bilər.',
    ],
];
