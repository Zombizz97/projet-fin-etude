<?php

return [
    'namespace' => env('PROMETHEUS_NAMESPACE', 'smashconnect'),

    'storage' => env('PROMETHEUS_STORAGE', 'in_memory'),

    'redis' => [
        'host' => env('PROMETHEUS_REDIS_HOST', env('REDIS_HOST', '127.0.0.1')),
        'port' => env('PROMETHEUS_REDIS_PORT', env('REDIS_PORT', 6379)),
        'password' => env('PROMETHEUS_REDIS_PASSWORD', env('REDIS_PASSWORD', null)),
        'timeout' => 0.1,
        'read_timeout' => 0.1,
        'persistent_connections' => false,
        'prefix' => env('PROMETHEUS_REDIS_PREFIX', 'PROMETHEUS_'),
    ],

    'http_buckets' => [0.01, 0.05, 0.1, 0.25, 0.5, 0.75, 1.0, 1.5, 2.0, 5.0],

    'collect_db_metrics' => env('PROMETHEUS_COLLECT_DB', true),

    'collect_user_metrics' => env('PROMETHEUS_COLLECT_USER', true),

    'collect_forum_metrics' => env('PROMETHEUS_COLLECT_FORUM', true),

    'ignored_routes' => [
        'api/metrics',
        '_debugbar.*',
        'telescope*',
    ],
];
