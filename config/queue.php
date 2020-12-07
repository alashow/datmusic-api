<?php

return [

    'default' => env('QUEUE_DRIVER', 'redis'),

    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('QUEUE_REDIS_CONNECTION', 'default'),
            'queue' => 'default',
            'retry_after' => 90,
            'block_for' => null,
        ],

    ],

    'failed' => [
        'database' => env('DB_CONNECTION', null),
        'table' => env('QUEUE_FAILED_TABLE', null),
    ],

];
