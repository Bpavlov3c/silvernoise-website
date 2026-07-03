<?php

return [
    'name'            => env('APP_NAME', 'Silvernoise'),
    'env'             => env('APP_ENV', 'production'),
    'debug'           => (bool) env('APP_DEBUG', false),
    'url'             => env('APP_URL', 'https://api.silvernoise.net'),
    'timezone'        => 'UTC',
    'locale'          => 'en',
    'fallback_locale' => 'en',
    'faker_locale'    => 'en_US',
    'key'             => env('APP_KEY'),
    'cipher'          => 'AES-256-CBC',
    'maintenance'     => ['driver' => 'file'],
    'providers'       => [],
    'aliases'         => [],
];
