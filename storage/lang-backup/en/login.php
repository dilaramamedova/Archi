<?php

// Login page + shared login modal (EN).

return [
    'title' => 'Sign in — ARCHİ',

    'head' => [
        'tag' => 'Welcome',
        'title' => 'Sign in',
        'subtitle' => 'Sign in to your account — buyer, seller or master.',
    ],

    'success' => 'Signed in successfully!',

    'form' => [
        'identifier_label' => 'E-mail or phone',
        'identifier_placeholder' => 'email@example.com',
        'password_label' => 'Password',
        'password_placeholder' => 'Password',
        'remember' => 'Remember me',
        'forgot' => 'Forgot password?',
        'submit' => 'Sign in',
        'no_account' => "Don't have an account?",
        'sign_up' => 'Sign up',
    ],

    'forgot' => 'Forgot your password?',
    'forgot_subtitle' => 'Enter your email and we will send you a password reset link.',
    'forgot_success' => 'Password reset link has been sent to your email.',
    'forgot_submit' => 'Send link',
    'back_to_login' => 'Back to login',

    'modal' => [
        'close' => 'Close',
    ],

    'errors' => [
        'invalid_credentials' => 'Invalid e-mail/phone or password.',
        'pending_approval' => 'Your account has not been approved by an administrator yet. Please wait.',
        'rejected' => 'Your account has been rejected. Please contact us for details.',
        'blocked' => 'Your account has been blocked. Please contact us for details.',
        'not_active' => 'Your account is not active.',
    ],
];
