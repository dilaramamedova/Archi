<?php

// Business register page (EN).

return [
    'title' => 'Quick sign up — ARCHİ',

    'head' => [
        'tag' => 'Join ARCHİ',
        'title' => 'Sign up',
        'subtitle' => 'It takes 30 seconds. Pick an account type, fill in 3 fields — you are done.',
    ],

    'roles' => [
        'buyer' => [
            'title' => 'Buyer',
            'desc' => 'Buy materials, find masters, place orders.',
        ],
        'master' => [
            'title' => 'Master / specialist',
            'desc' => 'Offer services, win customers.',
        ],
        'business' => [
            'title' => 'Business / seller',
            'desc' => 'List your products and sell.',
        ],
    ],

    'form' => [
        'name_label' => 'Full name',
        'name_placeholder' => 'Your first and last name',
        'contact_label' => 'E-mail or phone',
        'contact_placeholder' => 'email@example.com or +994 50 …',
        'password_label' => 'Password',
        'password_placeholder' => 'At least 6 characters',
        'terms' => 'I agree to the Terms of use and the Privacy policy',
        'submit' => 'Sign up',
        'have_account' => 'Already have an account?',
        'sign_in' => 'Sign in',
    ],

    'note' => 'For Master and Business accounts the extra details (specialization, documents, company) are completed in your profile after sign-up.',
];
