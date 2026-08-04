<?php

// Specialist cabinet — security (EN). Figma node 831:13282.

return [
    'title' => 'specialist-profile-edit · Security — ARCHİ',

    'crumbs' => [
        'panel' => 'Specialist panel',
        'separator' => '/',
        'current' => 'Profile editing',
    ],
    'heading' => 'Specialist profile editing',
    'status' => 'Published',
    'view_profile' => 'View profile ↗',

    'nav' => [
        'main' => 'Basic information',
        'portfolio' => 'Portfolio',
        'portfolio_count' => '24',
        'services' => 'Services and prices',
        'services_count' => '4',
        'schedule' => 'Work schedule',
        'reviews' => 'Reviews',
        'reviews_count' => '416',
        'notifications' => 'Notifications',
        'security' => 'Security',
    ],
    'progress' => [
        'label' => 'Profile completeness',
        'value' => '78%',
        'hint' => 'Add a certificate — +12%',
    ],

    'password' => [
        'title' => 'Change password',
        'desc' => 'At least 8 characters, one uppercase letter and a digit.',
        'current_label' => 'Current password',
        'new_label' => 'New password',
        'repeat_label' => 'Repeat new password',
        'mask' => '••••••••',
        'value' => 'Archi2024',
        'eye' => '👁️',
        'eye_label' => 'Show password',
        'submit' => 'Update password',
    ],

    'twofa' => [
        'title' => 'Two-factor authentication (2FA)',
        'desc' => 'Require an SMS code when signing in to your account.',
    ],

    'sessions' => [
        'title' => 'Active sessions',
        'this_device' => 'This device',
        'logout' => 'Sign out',
        'loading' => 'Loading sessions...',
    ],

    'danger' => [
        'title' => 'Danger zone',
        'desc' => 'These actions cannot be undone.',
        'deactivate_desc' => 'If you deactivate the profile, it will be removed from the catalog and new requests will stop.',
        'deactivate' => 'Deactivate profile',
        'confirm_text' => 'Enter your current password to deactivate your account. This action cannot be undone.',
        'password_label' => 'Enter your password',
        'confirm_deactivate' => 'Yes, deactivate',
        'cancel' => 'Cancel',
    ],

    'save' => [
        'unsaved' => 'You have unsaved changes',
        'cancel' => 'Cancel',
        'save' => 'Save',
        'saved' => 'Changes saved.',
    ],
];
