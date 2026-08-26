<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    /*
    |--------------------------------------------------------------------------
    | REST API client key
    |--------------------------------------------------------------------------
    |
    | Identifies the client APPLICATION calling /api/* via the X-API-KEY header.
    | This is separate from Sanctum, which identifies the USER. Read through
    | config (never env() at call time) so `php artisan config:cache` works.
    |
    | Set API_KEY in .env to switch the layer on; while it is empty the
    | middleware steps aside so an existing installation keeps working.
    |
    */
    'api' => [
        'key' => env('API_KEY'),
    ],

];
