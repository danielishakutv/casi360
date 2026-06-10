<?php

return [
    'default' => env('MAIL_MAILER', 'smtp'),

    'mailers' => [
        // Defaults target ZeptoMail (Global / .com). On the server, set
        // MAIL_USERNAME=emailapikey and MAIL_PASSWORD=<ZeptoMail Send token>
        // in .env; everything else here is a sensible default you can still
        // override via env.
        'smtp' => [
            'transport' => 'smtp',
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', 'smtp.zeptomail.com'),
            'port' => env('MAIL_PORT', 587),
            'encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url(env('APP_URL', 'https://casi360.com'), PHP_URL_HOST)),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],
    ],

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'noreply@casi360.com'),
        'name' => env('MAIL_FROM_NAME', 'CASI360'),
    ],
];
