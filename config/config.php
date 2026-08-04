<?php

declare(strict_types=1);

use App\Core\Env;

return [
    'app' => [
        'name'  => Env::get('APP_NAME', 'API Eventos ESPOL'),
        'env'   => Env::get('APP_ENV', 'local'),
        'debug' => Env::bool('APP_DEBUG', false),
        'url'   => Env::get('APP_URL', 'http://localhost:8000'),
    ],

    // Conexion a Supabase (PostgreSQL) via PDO.
    'db' => [
        'host'     => Env::get('DB_HOST', 'localhost'),
        'port'     => Env::get('DB_PORT', '5432'),
        'name'     => Env::get('DB_NAME', 'postgres'),
        'user'     => Env::get('DB_USER', 'postgres'),
        'password' => Env::get('DB_PASSWORD', ''),
        'sslmode'  => Env::get('DB_SSLMODE', 'require'),
    ],

    'cors' => [
        'allowed_origins' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) Env::get('CORS_ALLOWED_ORIGINS', '*'))
        ))),
        'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'Accept'],
        'max_age'         => 86400,
    ],
];
