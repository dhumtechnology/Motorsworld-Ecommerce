<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'culqi' => [
        // true = simula Culqi sin cuenta/llaves (solo desarrollo local)
        'fake' => (bool) env('CULQI_FAKE', false),
        'public_key' => env('CULQI_PUBLIC_KEY'),
        'secret_key' => env('CULQI_SECRET_KEY'),
        'order_expiration_hours' => (int) env('CULQI_ORDER_EXPIRATION_HOURS', 24),
    ],

    'mercadopago' => [
        // Legado: el checkout usa Culqi. Se mantiene por cobros históricos.
        'fake' => (bool) env('MERCADOPAGO_FAKE', true),
        'public_key' => env('MERCADOPAGO_PUBLIC_KEY'),
        'access_token' => env('MERCADOPAGO_ACCESS_TOKEN'),
        'webhook_secret' => env('MERCADOPAGO_WEBHOOK_SECRET'),
    ],

    'decolecta' => [
        'token' => env('DECOLECTA_API_TOKEN'),
        'base_url' => env('DECOLECTA_BASE_URL', 'https://api.decolecta.com/v1'),
        'referer' => env('DECOLECTA_REFERER', 'https://apis.net.pe/tipo-de-cambio-sunat-api'),
    ],

];
