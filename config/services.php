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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    'africastalking' => [
        'api_url' => env('AFRICASTALKING_API_URL', 'https://api.africastalking.com/version1/messaging'),
        'username' => env('AFRICASTALKING_USERNAME'),
        'api_key' => env('AFRICASTALKING_API_KEY'),
        'from' => env('AFRICASTALKING_FROM', 'AFRICASTALKING'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', env('APP_URL').'/contacts/google/callback'),
    ],

    'marz_wallet' => [
        'api_url' => env('MARZ_WALLET_API_URL', 'https://wallet.wearemarz.com/api/v1'),
        'api_key' => env('MARZ_WALLET_API_KEY'),
        'callback_url' => env('MARZ_WALLET_CALLBACK_URL', 'https://webhook.site/cb00d62e-6a81-4e36-ad5e-1a0821c7ad87'), // Use webhook.site for testing, update to your domain for production
    ],

];
