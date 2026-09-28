<?php

return [

    'paths' => [
        'api/*',
        'login',
        'logout',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:5173',
        'http://localhost:5174',
        'https://dasbordportfolio.vercel.app', // ton domaine Vercel
        'https://portfolio-frank-landry.vercel.app', // ✅ ton vrai domaine Vercel
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false, // ❌ plus besoin de cookies CSRF
];