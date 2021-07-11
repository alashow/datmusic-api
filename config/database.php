<?php
/**
 * Copyright (c) 2017  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

return [
    'default' => 'sqlite',
    'migrations' => 'migrations',

    'connections' => [
        'sqlite' => [
            'driver'   => 'sqlite',
            'database' => env('DB_DATABASE', storage_path('app/database/minerva.db')),
            'prefix'   => env('DB_PREFIX', ''),
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
