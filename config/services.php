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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'cloudinary' => [
        'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
        'api_key' => env('CLOUDINARY_API_KEY'),
        'api_secret' => env('CLOUDINARY_API_SECRET'),
    ],

    'bakong' => [
        'api_url' => env('BAKONG_API_URL', 'https://api-bakong.nbc.gov.kh'),
        'token' => env('BAKONG_API_KEY'),
        'merchant' => [
            'bakong_id' => env('BAKONG_MERCHANT_ID', env('BAKONG_ACCOUNT_ID')),
            'name' => env('BAKONG_MERCHANT_NAME', env('BAKONG_RECEIVER_NAME')),
            'city' => env('BAKONG_MERCHANT_CITY', env('BAKONG_RECEIVER_CITY', 'PHNOM PENH')),
            'acquiring_bank' => env('BAKONG_ACQUIRING_BANK'),
        ],
    ],
];
