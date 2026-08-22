<?php

return [
    'user_password_min' => (int) env('USER_PASSWORD_MIN', 8),
    'user_password_max' => (int) env('USER_PASSWORD_MAX', 128),
    'user_password_requires_at' => (bool) env('USER_PASSWORD_REQUIRES_AT', true),
    'temporary_password_lifetime_days' => (int) env('TEMPORARY_PASSWORD_LIFETIME_DAYS', 7),
    'common_passwords' => [
        'password@',
        'password1@',
        'password123@',
        'qwerty123@',
        'welcome1@',
        'admin123@',
        'aviatechnik@',
        'aviatechnik1@',
        '12345678@',
        '123456789@',
    ],

    'rate_limits' => [
        'login_account_attempts' => 5,
        'login_account_decay_seconds' => 300,
        'login_ip_attempts' => 20,
        'login_ip_decay_seconds' => 300,
        'current_password_attempts' => 5,
        'current_password_decay_seconds' => 600,
    ],
];
