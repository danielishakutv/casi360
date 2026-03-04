<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sanctum Configuration
    |--------------------------------------------------------------------------
    */

    /*
    | Stateful Domains
    | 
    | Requests from these domains will receive stateful API authentication
    | via Sanctum's cookie-based auth. Your frontend domain MUST be here.
    */
    'stateful' => explode(',', env(
        'SANCTUM_STATEFUL_DOMAINS',
        sprintf(
            '%s%s%s',
            'casi360.com,www.casi360.com',
            env('APP_URL') ? ',' . parse_url(env('APP_URL'), PHP_URL_HOST) : '',
            env('FRONTEND_URL') ? ',' . parse_url(env('FRONTEND_URL'), PHP_URL_HOST) : '',
        )
    )),

    /*
    | Sanctum Guards
    */
    'guard' => ['web'],

    /*
    | Token Expiration (minutes)
    | For API token-based auth (mobile apps, external integrations)
    */
    'expiration' => env('TOKEN_EXPIRY_MINUTES', 60),

    /*
    | Token Prefix
    */
    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    /*
    | Middleware
    */
    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],
];
