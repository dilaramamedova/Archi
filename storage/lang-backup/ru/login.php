<?php

// Login page + shared login modal (RU).

return [
    'title' => 'Войти — ARCHİ',

    'head' => [
        'tag' => 'Добро пожаловать',
        'title' => 'Войти',
        'subtitle' => 'Войдите в аккаунт — покупателя, продавца или мастера.',
    ],

    'success' => 'Вход выполнен!',

    'form' => [
        'identifier_label' => 'E-mail или телефон',
        'identifier_placeholder' => 'email@example.com',
        'password_label' => 'Пароль',
        'password_placeholder' => 'Пароль',
        'remember' => 'Запомнить меня',
        'forgot' => 'Забыли пароль?',
        'submit' => 'Войти',
        'no_account' => 'Нет аккаунта?',
        'sign_up' => 'Зарегистрироваться',
    ],

    'forgot' => 'Забыли пароль?',
    'forgot_subtitle' => 'Введите свой e-mail, и мы отправим вам ссылку для сброса пароля.',
    'forgot_success' => 'Ссылка для сброса пароля отправлена на ваш e-mail.',
    'forgot_submit' => 'Отправить ссылку',
    'back_to_login' => 'Вернуться ко входу',

    'modal' => [
        'close' => 'Закрыть',
    ],

    'errors' => [
        'invalid_credentials' => 'Неверный e-mail/телефон или пароль.',
        'pending_approval' => 'Ваш аккаунт ещё не подтверждён администратором. Пожалуйста, подождите.',
        'rejected' => 'Ваш аккаунт отклонён. Свяжитесь с нами для уточнения.',
        'blocked' => 'Ваш аккаунт заблокирован. Свяжитесь с нами для уточнения.',
        'not_active' => 'Ваш аккаунт не активен.',
    ],
];
