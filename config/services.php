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

    'nextjs' => [
        'revalidate_url' => rtrim(env('NEXTJS_SITE_URL', env('FRONTEND_URL', 'http://localhost:3000')), '/') . '/api/revalidate',
        'revalidation_secret' => env('NEXTJS_REVALIDATION_SECRET'),
    ],

    'firebase' => [
        'credentials' => env('FIREBASE_CREDENTIALS'), // path to service account JSON
        'credentials_json' => env('FIREBASE_CREDENTIALS_JSON'), // or raw JSON string
        'project_id' => env('FIREBASE_PROJECT_ID'),
        // Web config (for dashboard Blade - pass to frontend)
        'api_key' => env('FIREBASE_API_KEY'),
        'auth_domain' => env('FIREBASE_AUTH_DOMAIN'),
        'storage_bucket' => env('FIREBASE_STORAGE_BUCKET'),
        'messaging_sender_id' => env('FIREBASE_MESSAGING_SENDER_ID'),
        'app_id' => env('FIREBASE_APP_ID'),
    ],

];
