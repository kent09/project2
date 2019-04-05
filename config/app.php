<?php

return [
    'name' => env('APP_NAME', 'Kryptonia'),
    'env' => env('APP_ENV', 'live'),
    'debug' => env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => 'America/Sao_Paulo',
    'locale' => 'en',
    'fallback_locale' => 'en',
    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',
    'log' => env('APP_LOG', 'single'),
    'log_level' => env('APP_LOG_LEVEL', 'debug'),

    // BLOCKIO
    'blockioapikey' => env('BLOCKIO_APIKEY'),
    'blockiopin' => env('BLOCKIO_PIN'),
    'blockioversion' => env('BLOCKIO_VERSION'),
    
    'maintenance' => env('MAINTENANCE', false),
    'domain_1' => env('DOMAIN_1', 'kryptonia.io'),
    'domain_2' => env('DOMAIN_2', 'the-superior-coin.com'),
    'admin_email' => env('ADMIN_EMAIL', 'admin@kryptonia.io'),
    'no_reply' => env('NOREPLY_EMAIL', 'noreply@kryptonia.io'),

    'frontend_url_email' => env('FRONTEND_URL','http://kapi.progeekz.biz'),

    'wallet' => [
        'ip' => env('WALLET_IP', '127.0.0.1'),
        'port_1' => env('WALLET_PORT_1', 18082),
        'port_2' => env('WALLET_PORT_2', 8082),
    ],

    'bot_host' => env('BOT_HOST', 'http://kryptonia.biz:1433'),
];