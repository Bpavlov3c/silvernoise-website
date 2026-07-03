<?php

return [
    'kvz' => [
        'api_key'  => env('KVZ_API_KEY'),
        'base_url' => env('KVZ_BASE_URL', 'https://api.kvzmusic.com/rest'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
];
