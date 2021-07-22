<?php
/**
 * Copyright (c) 2021  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

return [
    'default' => env('DB_CONNECTION', 'not-set'),
    'migrations' => 'migrations',

    'connections' => [
        'postgres' => [
            'driver'   => 'pgsql',
            'host'     => env('DB_HOST_POSTGRES', '127.0.0.1'),
            'port'     => env('DB_PORT_POSTGRES', '5432'),
            'database' => env('DB_DATABASE_POSTGRES', 'datmusic'),
            'username' => env('DB_USERNAME_POSTGRES', 'datmusic'),
            'password' => env('DB_PASSWORD_POSTGRES', 'datmusic'),
            'charset'  => 'utf8',
            'prefix'   => '',
            'schema'   => 'public',
            'sslmode'  => 'prefer',
        ],

        'minerva' => [
            'driver'   => 'sqlite',
            'database' => env('DB_DATABASE_MINERVA', storage_path('app/database/minerva.db')),
            'prefix'   => env('DB_PREFIX_MINERVA', ''),
        ],
    ],

    //  redis is used for caches and queues
    'redis'       => [
        'client'  => 'predis',
        'cluster' => false,

        'default' => [
            'host'     => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port'     => env('REDIS_PORT', 6379),
            'database' => 0,
        ],
    ],
];
