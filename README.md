# Netwatch

Network service latency probing tool for PHP. Measures connectivity and response times to Redis, PostgreSQL, MySQL, S3, HTTP endpoints, and raw TCP services with statistical analysis.

## TODO
- [ ] `php artisan optimize` not working. We probably need to pass the classname and the arguments instead
- [ ] Include hostname/identification on the output

## Maybe
- [ ] `/netwatch/health?token=env(token)` (maybe POST) (or maybe a form)
- [ ] Post results to some endpoint after `php artisan netwatch:run`
- [ ] Detect env/cached config. Maybe boot application once and cache it with a md5(.env)
- [ ] Maybe `Concurrency::driver(’queue’)` for async when calling `/netwatch/health`
- [ ] Store (cleanup) metrics to view on a dashboard
- [ ] Use Inertia (3) for the dashboard with polling


## Features

- **Multiple probe types** — HTTP, TCP/IP, Redis (php-redis), MySQL/PostgreSQL/SQLite (PDO), AWS S3
- **Statistical analysis** — min, max, avg, p50, p95, p99 for connect, request, and total latency
- **Parallel execution** — probes run concurrently via subprocesses (default in CLI)
- **Per-probe configuration** — individual iteration counts, enable/disable flags, lazy-loaded closures
- **Laravel integration** — service provider with auto-discovery, Artisan command, and health dashboard
- **Standalone CLI** — works with any PHP project via Symfony Console
- **Fail-fast** — stops probing after 3 consecutive failures

## Requirements

- PHP 8.3+
- `ext-curl` (for HTTP and S3 probes)
- `ext-redis` (for Redis probe, optional)
- `ext-pdo` (for database probes, optional)

## Installation

```bash
composer require mathiasgrimm/netwatch
```

## Quick Start

### CLI

Generate a config file and run:

```bash
# Generate config
vendor/bin/netwatch netwatch:init

# Run all probes
vendor/bin/netwatch netwatch:run

# Run a specific probe with 20 iterations
vendor/bin/netwatch netwatch:run --probe redis --iterations 20

# JSON output
vendor/bin/netwatch netwatch:run --json
```

### Programmatic

```php
use Mathiasgrimm\Netwatch\Netwatch;
use Mathiasgrimm\Netwatch\Probe\HttpProbe;
use Mathiasgrimm\Netwatch\Probe\TcpPingProbe;

$netwatch = new Netwatch([
    'example' => ['probe' => new HttpProbe('https://example.com')],
    'dns'     => ['probe' => new TcpPingProbe('8.8.8.8', 53)],
], iterations: 10);

$results = $netwatch->run();

foreach ($results as $name => $aggregate) {
    echo "$name: avg={$aggregate->stats['total_ms']['avg']}ms p99={$aggregate->stats['total_ms']['p99']}ms\n";
}
```

## Configuration

### Standalone Config File

Create a `netwatch.php` in your project root (or use `netwatch:init` to generate one):

```php
<?php

use Mathiasgrimm\Netwatch\Probe\PhpRedisProbe;
use Mathiasgrimm\Netwatch\Probe\PdoProbe;
use Mathiasgrimm\Netwatch\Probe\HttpProbe;
use Mathiasgrimm\Netwatch\Probe\TcpPingProbe;

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
        'app' => [
            'probe' => new HttpProbe('https://example.com'),
        ],
        'cloudflare' => [
            'probe' => new TcpPingProbe('1.1.1.1', 443),
        ],
    ],
];
```

### Per-Probe Options

Each probe entry supports:

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `probe` | `ProbeInterface\|Closure` | required | Probe instance or closure returning one |
| `enabled` | `bool` | `true` | Skip this probe when `false` |
| `iterations` | `int` | global default | Override iteration count for this probe |

```php
'redis' => [
    'enabled' => true,
    'probe' => fn () => new PhpRedisProbe('tcp://127.0.0.1:6379'),
    'iterations' => 20,
],
```

Closures are resolved lazily at runtime, which is useful for probes that depend on environment variables or framework config.

## Available Probes

### HttpProbe

Measures HTTP endpoint latency using cURL. Reports connect time and request time separately.

```php
new HttpProbe(
    url: 'https://example.com',
    method: 'GET',          // HTTP method (default: GET)
    headers: [],            // Custom headers
    timeout: 3.0,           // Timeout in seconds
)
```

### TcpPingProbe

Measures raw TCP connection latency via `fsockopen`.

```php
new TcpPingProbe(
    host: '8.8.8.8',
    port: 53,
    timeout: 3.0,
)
```

### PdoProbe

Measures database connectivity by opening a PDO connection and running `SELECT 1`.

```php
new PdoProbe(
    dsn: 'mysql:host=127.0.0.1;port=3306;dbname=mydb',
    username: 'root',
    password: 'secret',
    timeout: 3,
)
```

Supports any PDO driver: MySQL, PostgreSQL, SQLite, etc.

### PhpRedisProbe

Measures Redis latency using the php-redis extension. Connects, authenticates, and runs `PING`.

```php
new PhpRedisProbe(
    address: 'tcp://127.0.0.1:6379',
    username: null,
    password: null,
    timeout: 3.0,
)
```

### S3Probe

Measures AWS S3 bucket latency by performing a `HEAD` request with AWS Signature V4 authentication (no SDK required).

```php
new S3Probe(
    bucket: 'my-bucket',
    region: 'us-east-1',
    key: 'AKIAIOSFODNN7EXAMPLE',
    secret: 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
    endpoint: null,         // Custom endpoint (e.g., MinIO)
    timeout: 3.0,
)
```

### Custom Probes

Implement `ProbeInterface` to create your own:

```php
use Mathiasgrimm\Netwatch\Contract\ProbeInterface;
use Mathiasgrimm\Netwatch\Result\ProbeResult;

class MyProbe implements ProbeInterface
{
    public function probe(): ProbeResult
    {
        $start = microtime(true);
        // ... your logic ...
        $elapsed = (microtime(true) - $start) * 1000;

        return new ProbeResult(
            connectMs: $elapsed,
            requestMs: 0,
            totalMs: $elapsed,
            success: true,
        );
    }

    public function name(): string
    {
        return 'my-probe';
    }
}
```

## CLI Reference

### `netwatch:run`

Run probes and display latency statistics.

```
vendor/bin/netwatch netwatch:run [options]
```

| Option | Short | Description |
|--------|-------|-------------|
| `--config` | `-c` | Config file path (default: `netwatch.php`) |
| `--iterations` | `-i` | Override iteration count for all probes |
| `--probe` | `-p` | Run only a specific probe by name |
| `--sequential` | | Run probes sequentially (default: parallel) |
| `--json` | | Output results as JSON |
| `--without-results` | | Exclude individual iteration results from JSON |

### `netwatch:init`

Generate a starter config file.

```
vendor/bin/netwatch netwatch:init [options]
```

| Option | Short | Description |
|--------|-------|-------------|
| `--laravel` | `-l` | Generate Laravel-aware config (auto-detected) |
| `--force` | `-f` | Overwrite existing `netwatch.php` |

## Output

### Table Output (default)

```
+--------+------------+----------+---------+----------+----------+----------+----------+----------+----------+
| Probe  | Iterations | Failures | Metric  | Min (ms) | Max (ms) | Avg (ms) | P50 (ms) | P95 (ms) | P99 (ms) |
+--------+------------+----------+---------+----------+----------+----------+----------+----------+----------+
| redis  | 10         | 0        | connect | 0.312    | 0.891    | 0.523    | 0.487    | 0.856    | 0.884    |
|        |            |          | request | 0.098    | 0.234    | 0.142    | 0.131    | 0.221    | 0.231    |
|        |            |          | total   | 0.421    | 1.102    | 0.665    | 0.618    | 1.054    | 1.092    |
+--------+------------+----------+---------+----------+----------+----------+----------+----------+----------+
```

### JSON Output

```json
{
  "redis": {
    "name": "redis:tcp://127.0.0.1:6379",
    "iterations": 10,
    "stats": {
      "connect_ms": { "min": 0.312, "max": 0.891, "avg": 0.523, "p50": 0.487, "p95": 0.856, "p99": 0.884 },
      "request_ms": { "min": 0.098, "max": 0.234, "avg": 0.142, "p50": 0.131, "p95": 0.221, "p99": 0.231 },
      "total_ms":   { "min": 0.421, "max": 1.102, "avg": 0.665, "p50": 0.618, "p95": 1.054, "p99": 1.092 }
    },
    "failures": 0,
    "results": [ ... ]
  }
}
```

## Laravel Integration

Netwatch auto-registers via Laravel package discovery. No manual provider registration needed.

### Publish Config

```bash
php artisan vendor:publish --tag=netwatch-config
```

This creates `config/netwatch.php` with pre-configured probes that read from your existing Laravel environment variables (`DB_*`, `REDIS_*`, `AWS_*`, `APP_URL`).

### Artisan Command

```bash
php artisan netwatch:run
php artisan netwatch:run --probe=redis --iterations=20 --json
```

### Health Dashboard

Enable the health dashboard route by setting `NETWATCH_HEALTH_ENABLED=true` in your `.env`:

```env
NETWATCH_HEALTH_ENABLED=true
NETWATCH_PATH=netwatch
```

Access the dashboard at `/netwatch/health`. It supports:

- **HTML view** — interactive dashboard with per-probe stats and a raw JSON panel
- **JSON view** — append `?format=json` or use `Accept: application/json`
- **Probe filtering** — `?probes=redis,database`
- **Compact JSON** — `?without_results=1`

### Authorization

By default, the health route is accessible only in `local` environments. To configure access in other environments, publish the service provider:

```bash
php artisan vendor:publish --tag=netwatch-provider
```

Then edit `app/Providers/NetwatchServiceProvider.php`:

```php
protected function gate(): void
{
    Gate::define('viewNetwatch', function ($user = null) {
        return in_array(optional($user)->email, [
            'admin@example.com',
        ]);
    });
}
```

You can also use the static auth callback:

```php
Netwatch::auth(function ($request) {
    return $request->user()?->isAdmin();
});
```

### Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `NETWATCH_ITERATIONS` | `10` | Default probe iterations |
| `NETWATCH_HEALTH_ENABLED` | `false` | Enable health dashboard route |
| `NETWATCH_DOMAIN` | `null` | Domain for health route |
| `NETWATCH_PATH` | `netwatch` | URL path prefix for health route |
| `NETWATCH_PROBE_*_ENABLED` | `false` | Enable individual probes |

## Statistical Analysis

For each probe, Netwatch runs the configured number of iterations and computes statistics over successful runs. Three timing metrics are tracked:

| Metric | Description |
|--------|-------------|
| `connect_ms` | Time to establish the connection |
| `request_ms` | Time to complete the request after connection |
| `total_ms` | End-to-end latency (connect + request) |

For each metric, the following statistics are computed:

| Stat | Description |
|------|-------------|
| `min` | Minimum observed value |
| `max` | Maximum observed value |
| `avg` | Arithmetic mean |
| `p50` | 50th percentile (median) |
| `p95` | 95th percentile |
| `p99` | 99th percentile |

Percentiles use linear interpolation between nearest-rank values.

## Testing

```bash
composer install
vendor/bin/pest
```

## License

MIT
