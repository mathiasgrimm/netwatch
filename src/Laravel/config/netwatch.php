<?php

use Mathiasgrimm\Netwatch\Laravel\Http\Middleware\Authorize;
use Mathiasgrimm\Netwatch\Probe\HttpProbe;
use Mathiasgrimm\Netwatch\Probe\PdoProbe;
use Mathiasgrimm\Netwatch\Probe\PhpRedisProbe;
use Mathiasgrimm\Netwatch\Probe\S3Probe;
use Mathiasgrimm\Netwatch\Probe\TcpPingProbe;

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
    */
    'probes' => [

        'database' => [
            'enabled' => env('NETWATCH_PROBE_DATABASE_ENABLED', false),
            'probe' => fn () => new PdoProbe(
                env('DB_CONNECTION').':host='.env('DB_HOST').';port='.env('DB_PORT').';dbname='.env('DB_DATABASE'),
                env('DB_USERNAME'),
                env('DB_PASSWORD'),
            ),
        ],

        'redis' => [
            'enabled' => env('NETWATCH_PROBE_REDIS_ENABLED', false),
            'probe' => fn () => new PhpRedisProbe(
                address: env('REDIS_HOST').':'.env('REDIS_PORT'),
                username: env('REDIS_USERNAME'),
                password: env('REDIS_PASSWORD'),
            ),
        ],

        's3' => [
            'enabled' => env('NETWATCH_PROBE_S3_ENABLED', false),
            'probe' => fn () => new S3Probe(
                bucket: env('AWS_BUCKET'),
                region: env('AWS_DEFAULT_REGION'),
                key: env('AWS_ACCESS_KEY_ID'),
                secret: env('AWS_SECRET_ACCESS_KEY'),
                endpoint: env('AWS_ENDPOINT'),
            ),
        ],

        'app' => [
            'enabled' => env('NETWATCH_PROBE_APP_ENABLED', false),
            'probe' => fn () => new HttpProbe(env('APP_URL')),
        ],

        'cloudflare-dns' => [
            'enabled' => env('NETWATCH_PROBE_CLOUDFLARE_DNS_ENABLED', false),
            'probe' => fn () => new TcpPingProbe('1.1.1.1', 53),
        ],

        'google-dns' => [
            'enabled' => env('NETWATCH_PROBE_GOOGLE_DNS_ENABLED', false),
            'probe' => fn () => new TcpPingProbe('8.8.8.8', 53),
        ],

        'github.com' => [
            'enabled' => env('NETWATCH_PROBE_GITHUB_COM_ENABLED', false),
            'probe' => fn () => new HttpProbe('https://github.com'),
        ],

        'github.org' => [
            'enabled' => env('NETWATCH_PROBE_GITHUB_ORG_ENABLED', false),
            'probe' => fn () => new HttpProbe('https://github.org'),
        ],

    ],
];
