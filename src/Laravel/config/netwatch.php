<?php

use MathiasGrimm\Netwatch\Laravel\Http\Middleware\Authorize;
use MathiasGrimm\Netwatch\Probe\HttpProbe;
use MathiasGrimm\Netwatch\Probe\PdoProbe;
use MathiasGrimm\Netwatch\Probe\PhpRedisProbe;
use MathiasGrimm\Netwatch\Probe\S3Probe;
use MathiasGrimm\Netwatch\Probe\TcpPingProbe;

return [
    /*
    |--------------------------------------------------------------------------
    | Default Iterations
    |--------------------------------------------------------------------------
    |
    | The number of times each probe will be executed to compute statistics.
    |
    */
    'iterations' => (int) env('NETWATCH_ITERATIONS', 10),

    /*
    |--------------------------------------------------------------------------
    | Health Route
    |--------------------------------------------------------------------------
    |
    | Optionally expose a health-check endpoint that runs all probes.
    | The route will be registered at {path}/health (e.g. /netwatch/health).
    | Middleware defaults to ['web', Authorize::class] like Horizon/Nova.
    | Publish the NetwatchServiceProvider to customise the gate.
    |
    */
    'health_route' => [
        'enabled' => (bool) env('NETWATCH_HEALTH_ENABLED', false),
        'domain' => env('NETWATCH_DOMAIN'),
        'path' => env('NETWATCH_PATH', 'netwatch'),
        'middleware' => ['web', Authorize::class],
        'token' => env('NETWATCH_HEALTH_TOKEN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Probes
    |--------------------------------------------------------------------------
    |
    | Define your network probes here. Each probe should have a unique key
    | and contain a 'probe' instance implementing ProbeInterface.
    | Set the 'enabled' flag to true (via env) for the probes you want to use.
    |
    | 'thresholds' are latency limits in milliseconds, checked against the
    | probe's total p95 on the dashboard (warn = amber, crit = red).
    | Set an env value to null/empty to disable that threshold.
    |
    */
    'probes' => [

        'database' => [
            'enabled' => env('NETWATCH_PROBE_DATABASE_ENABLED', false),
            'thresholds' => [
                'warn' => env('NETWATCH_PROBE_DATABASE_WARN_MS', 50),
                'crit' => env('NETWATCH_PROBE_DATABASE_CRIT_MS', 100),
            ],
            'probe' => [
                PdoProbe::class => [
                    env('DB_CONNECTION').':host='.env('DB_HOST').';port='.env('DB_PORT').';dbname='.env('DB_DATABASE'),
                    env('DB_USERNAME'),
                    env('DB_PASSWORD'),
                ],
            ],
        ],

        'database-tcp' => [
            'enabled' => env('NETWATCH_PROBE_DATABASE_TCP_ENABLED', false),
            'thresholds' => [
                'warn' => env('NETWATCH_PROBE_DATABASE_TCP_WARN_MS', 10),
                'crit' => env('NETWATCH_PROBE_DATABASE_TCP_CRIT_MS', 25),
            ],
            'probe' => [
                TcpPingProbe::class => [
                    parse_url((string) env('DB_HOST'), PHP_URL_HOST) ?: env('DB_HOST'),
                    (int) (env('DB_PORT') ?: match (env('DB_CONNECTION')) {
                        'pgsql' => 5432,
                        'sqlsrv' => 1433,
                        default => 3306,
                    }),
                ],
            ],
        ],

        'redis' => [
            'enabled' => env('NETWATCH_PROBE_REDIS_ENABLED', false),
            'thresholds' => [
                'warn' => env('NETWATCH_PROBE_REDIS_WARN_MS', 50),
                'crit' => env('NETWATCH_PROBE_REDIS_CRIT_MS', 100),
            ],
            'probe' => [
                PhpRedisProbe::class => [
                    env('REDIS_HOST').':'.env('REDIS_PORT'),
                    env('REDIS_USERNAME'),
                    env('REDIS_PASSWORD'),
                ],
            ],
        ],

        'redis-tcp' => [
            'enabled' => env('NETWATCH_PROBE_REDIS_TCP_ENABLED', false),
            'thresholds' => [
                'warn' => env('NETWATCH_PROBE_REDIS_TCP_WARN_MS', 5),
                'crit' => env('NETWATCH_PROBE_REDIS_TCP_CRIT_MS', 25),
            ],
            'probe' => [
                TcpPingProbe::class => [
                    parse_url((string) env('REDIS_HOST'), PHP_URL_HOST) ?: env('REDIS_HOST'),
                    (int) (env('REDIS_PORT') ?: 6379),
                ],
            ],
        ],

        's3' => [
            'enabled' => env('NETWATCH_PROBE_S3_ENABLED', false),
            'thresholds' => [
                'warn' => env('NETWATCH_PROBE_S3_WARN_MS', 150),
                'crit' => env('NETWATCH_PROBE_S3_CRIT_MS', 500),
            ],
            'probe' => [
                S3Probe::class => [
                    env('AWS_BUCKET'),
                    env('AWS_DEFAULT_REGION'),
                    env('AWS_ACCESS_KEY_ID'),
                    env('AWS_SECRET_ACCESS_KEY'),
                    env('AWS_ENDPOINT'),
                ],
            ],
        ],

        'app' => [
            'enabled' => env('NETWATCH_PROBE_APP_ENABLED', false),
            'thresholds' => [
                'warn' => env('NETWATCH_PROBE_APP_WARN_MS', 500),
                'crit' => env('NETWATCH_PROBE_APP_CRIT_MS', 1000),
            ],
            'probe' => [
                HttpProbe::class => [
                    env('APP_URL'),
                ],
            ],
        ],

        'cloudflare-dns' => [
            'enabled' => env('NETWATCH_PROBE_CLOUDFLARE_DNS_ENABLED', false),
            'thresholds' => [
                'warn' => env('NETWATCH_PROBE_CLOUDFLARE_DNS_WARN_MS', 35),
                'crit' => env('NETWATCH_PROBE_CLOUDFLARE_DNS_CRIT_MS', 50),
            ],
            'probe' => [
                TcpPingProbe::class => [
                    '1.1.1.1',
                    53,
                ],
            ],
        ],

        'google-dns' => [
            'enabled' => env('NETWATCH_PROBE_GOOGLE_DNS_ENABLED', false),
            'thresholds' => [
                'warn' => env('NETWATCH_PROBE_GOOGLE_DNS_WARN_MS', 35),
                'crit' => env('NETWATCH_PROBE_GOOGLE_DNS_CRIT_MS', 50),
            ],
            'probe' => [
                TcpPingProbe::class => [
                    '8.8.8.8',
                    53,
                ],
            ],
        ],

    ],
];
