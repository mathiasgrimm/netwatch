<?php

use MathiasGrimm\Netwatch\Probe\HttpProbe;
use MathiasGrimm\Netwatch\Probe\PdoProbe;
use MathiasGrimm\Netwatch\Probe\PhpRedisProbe;
use MathiasGrimm\Netwatch\Probe\TcpPingProbe;

return [
    'iterations' => 10,

    'probes' => [
        'redis' => [
            'probe' => new PhpRedisProbe('tcp://127.0.0.1:6379'),
        ],
        'mysql' => [
            'probe' => new PdoProbe('mysql:host=127.0.0.1;port=3306', 'root', ''),
        ],
        'pgsql' => [
            'probe' => new PdoProbe('pgsql:host=127.0.0.1;port=5432;dbname=postgres', 'postgres', ''),
        ],
        'laravel' => [
            'probe' => new HttpProbe('https://laravel.com'),
        ],
        'cloudflare' => [
            'probe' => new TcpPingProbe('1.1.1.1', 443),
        ],
        'google-dns' => [
            'probe' => new TcpPingProbe('8.8.8.8', 443),
        ],
    ],
];
