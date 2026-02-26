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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'apple' => [
        'client_id' => env('APPLE_CLIENT_ID'),
        'client_secret' => env('APPLE_CLIENT_SECRET'), // empty rehne do
        'key_id' => env('APPLE_KEY_ID'),
        'team_id' => env('APPLE_TEAM_ID'),
        'private_key' => env('APPLE_PRIVATE_KEY'), // absolute path
        'passphrase' => env('APPLE_PASSPHRASE'), // optional
        'signer' => env('APPLE_SIGNER', 'ES256'), // IMPORTANT
        'redirect' => env('APPLE_REDIRECT_URI'),
    ],

    'airwallex' => [

        'env' => env('AIRWALLEX_ENV', 'sandbox'),

        'sandbox' => [
            'base_url' => env('AIRWALLEX_SANDBOX_BASE_URL'),
            'client_id' => env('AIRWALLEX_SANDBOX_CLIENT_ID'),
            'api_key' => env('AIRWALLEX_SANDBOX_API_KEY'),
            'account_id' => env('AIRWALLEX_ACCOUNT_ID'),
        ],

        // 'production' => [
        //     'base_url' => env('AIRWALLEX_PROD_BASE_URL'),
        //     'client_id' => env('AIRWALLEX_PROD_CLIENT_ID'),
        //     'api_key' => env('AIRWALLEX_PROD_API_KEY'),
        // ],
    ],

    'esim' => [
        'api_user' => env('ESIM_API_USER'),
        'api_pass' => env('ESIM_API_PASS'),
        'api_url' => env('ESIM_API_URL'),
        'distributor_id' => env('ESIM_DISTRIBUTOR_ID', '14597879'),
        'admin_email' => env('ESIM_ADMIN_EMAIL'),
    ],
    'tly' => [
        'token' => env('TLY_TOKEN'),
    ],

];
