<?php

// Business cabinet — security (EN).

return [
    'title' => 'business-profile-edit · Security — ARCHİ',

    'crumbs' => [
        'panel' => 'Business panel',
        'separator' => '/',
        'current' => 'Edit profile',
    ],
    'heading' => 'Edit business profile',
    'status' => 'Published',
    'view_profile' => 'View profile ↗',

    'nav' => [
        'company' => 'Company details',
        'contact' => 'Contact details',
        'showrooms' => 'Showrooms',
        'showrooms_count' => '3',
        'products' => 'Products',
        'products_count' => '1,240',
        'notifications' => 'Notifications',
        'security' => 'Security',
    ],
    'progress' => [
        'label' => 'Profile completeness',
        'value' => '85%',
        'hint' => 'Add a video — +15%',
    ],

    'password' => [
        'title' => 'Change password',
        'desc' => 'At least 8 characters, one capital letter and a digit.',
        'current_label' => 'Current password',
        'new_label' => 'New password',
        'repeat_label' => 'Repeat new password',
        'mask' => '••••••••',
        'value' => 'Archi2024',
        'eye' => '👁',
        'submit' => 'Update password',
    ],

    'twofa' => [
        'title' => 'Two-factor authentication (2FA)',
        'desc' => 'Require an SMS code when signing in. Recommended for business accounts.',
    ],

    'sessions' => [
        'title' => 'Active sessions',
        'this_device' => 'This device',
        'logout' => 'Sign out',
        's1_device' => 'MacBook Pro · Safari',
        's1_meta' => 'Baku, Azerbaijan · Active now',
        's2_device' => 'iPhone 15 · ARCHİ App',
        's2_meta' => 'Baku, Azerbaijan · 2 hours ago',
        's3_device' => 'Windows · Chrome',
        's3_meta' => 'Sumgait · 3 days ago',
    ],

    'danger' => [
        'title' => 'Danger zone',
        'desc' => 'These actions cannot be undone.',
        'deactivate_desc' => 'If you deactivate the profile, your products will be hidden from the catalog and the profile will not appear in search.',
        'deactivate' => 'Deactivate profile',
    ],

    'save' => [
        'unsaved' => 'You have unsaved changes',
        'cancel' => 'Cancel',
        'save' => 'Save',
        'saved' => 'Changes saved.',
    ],
];
