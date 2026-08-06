<?php

// Register page (EN).

return [
    'title' => 'Sign up — ARCHİ',

    'head' => [
        'tag' => 'Join ARCHİ',
        'title' => 'Sign up',
        'subtitle' => 'Choose an account type — buyer, seller or master/specialist.',
    ],

    'success' => 'Registration complete!',

    'roles' => [
        'label' => 'Account type',
        'buyer' => [
            'title' => 'Buyer',
            'desc' => 'Buy materials, find masters, place orders.',
        ],
        'seller' => [
            'title' => 'Seller',
            'desc' => 'List your products and sell.',
        ],
        'master' => [
            'title' => 'Master / specialist',
            'desc' => 'Offer services, win customers.',
        ],
    ],

    'form' => [
        'first_name_label' => 'First name',
        'first_name_placeholder' => 'Your first name',
        'last_name_label' => 'Last name',
        'last_name_placeholder' => 'Your last name',
        'company_label' => 'Company / brand name',
        'company_placeholder' => 'e.g. ARCHI Build LLC',
        'specialization_label' => 'Specialization',
        'city_label' => 'City',
        'select_placeholder' => 'Select',
        'email_label' => 'E-mail',
        'email_placeholder' => 'email@example.com',
        'phone_label' => 'Phone',
        'phone_placeholder' => '+994 50 000 00 00',
        'password_label' => 'Password',
        'password_placeholder' => 'At least 6 characters',
        'password_confirm_label' => 'Repeat password',
        'password_confirm_placeholder' => 'Repeat password',
        'terms_link' => 'Terms of use',
        'terms_and' => 'and',
        'privacy_link' => 'Privacy policy',
        'terms_agree' => '— I agree',
        'submit' => 'Sign up',
        'info_note' => 'After registering as a specialist or seller, you will be asked to complete additional profile information.',
        'have_account' => 'Already have an account?',
        'sign_in' => 'Sign in',
    ],

    'specializations' => [
        'tiler' => 'Tiler',
        'electrician' => 'Electrician',
        'plumber' => 'Plumber',
        'painter' => 'Painter',
        'drywaller' => 'Drywaller',
        'architect' => 'Architect',
        'interior_designer' => 'Interior designer',
        'other' => 'Other',
    ],

    'cities' => [
        'baku' => 'Baku',
        'sumgayit' => 'Sumgayit',
        'ganja' => 'Ganja',
        'mingachevir' => 'Mingachevir',
        'shirvan' => 'Shirvan',
        'other' => 'Other',
    ],

    'pending_message' => 'Registration complete! You can sign in after your account is approved by an administrator.',
];
