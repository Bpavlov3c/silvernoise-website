<?php

return [
    'paths'                    => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods'          => ['*'],
    'allowed_origins'          => array_filter([
        env('FRONTEND_URL', 'https://silvernoise.net'),
        env('SELLER_URL',   'https://seller.silvernoise.net'),
        env('ADMIN_URL',    'https://admin.silvernoise.net'),
        // Forge preview domains — always allowed
        'https://silvernoise-website.on-forge.com',
        'https://silvernoise-admin.on-forge.com',
        'https://silvernoise-seller.on-forge.com',
        // Local development
        'http://localhost:3000',
        'http://localhost:3001',
        'http://localhost:3002',
    ]),
    'allowed_origins_patterns' => [],
    'allowed_headers'          => ['*'],
    'exposed_headers'          => [],
    'max_age'                  => 0,
    'supports_credentials'     => true,
];
