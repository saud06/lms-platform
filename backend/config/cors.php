<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter([
        'http://localhost:3000', 'http://127.0.0.1:3000',
        'http://localhost:3001', 'http://127.0.0.1:3001',
        'http://localhost:3002', 'http://127.0.0.1:3002',
        'https://lms-frontend-35zj.onrender.com', // Your actual frontend URL
        env('FRONTEND_URL'), // Dynamic frontend URL from Render
    ]),

    'allowed_origins_patterns' => [
        'https://*-lms-frontend*.onrender.com',
        'https://lms-frontend*.onrender.com',
        'https://*-lms-platform-frontend*.onrender.com',
        'https://lms-platform-frontend*.onrender.com',
        'https://*frontend*.onrender.com',
        // More flexible patterns for Render service variations
        'https://lms-frontend-*.onrender.com',
        'https://lms-frontend.onrender.com',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
