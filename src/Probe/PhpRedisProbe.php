<?php

declare(strict_types=1);

namespace Mathiasgrimm\Netwatch\Probe;

use Mathiasgrimm\Netwatch\Contract\ProbeInterface;
use Mathiasgrimm\Netwatch\Result\ProbeResult;

class PhpRedisProbe implements ProbeInterface
{
    private readonly string $host;

    private readonly int $port;

    private readonly string $scheme;

    public function __construct(
        string $address,
        private readonly ?string $username = null,
        private readonly ?string $password = null,
        private readonly float $timeout = 3.0,
    ) {
        $parsed = parse_url($address);

        $this->scheme = $parsed['scheme'] ?? 'tcp';
        $this->host = $parsed['host'] ?? $address;
        $this->port = $parsed['port'] ?? 6379;
    }

    public function probe(): ProbeResult
    {
        $start = hrtime(true);

        try {
            $redis = new \Redis;

            $useTls = in_array($this->scheme, ['tls', 'rediss', 'tls6', 'ssl'], true);
            $host = $useTls ? "tls://{$this->host}" : $this->host;

            $redis->connect($host, $this->port, $this->timeout);

            $connectMs = (hrtime(true) - $start) / 1_000_000;

            $requestStart = hrtime(true);

            if ($this->password !== null) {
                $auth = $this->username !== null
                    ? [$this->username, $this->password]
                    : $this->password;

                $redis->auth($auth);
            }

            $response = $redis->ping();

            $requestMs = (hrtime(true) - $requestStart) / 1_000_000;

            $redis->close();

            $success = $response === true || $response === '+PONG';

            return new ProbeResult(
                connectMs: $connectMs,
                requestMs: $requestMs,
                totalMs: $connectMs + $requestMs,
                success: $success,
                error: $success ? null : 'Unexpected response: '.var_export($response, true),
            );
        } catch (\Throwable $e) {
            $totalMs = (hrtime(true) - $start) / 1_000_000;
            $connectMs ??= $totalMs;
            $requestMs = $totalMs - $connectMs;

            return new ProbeResult(
                connectMs: $connectMs,
                requestMs: max(0, $requestMs),
                totalMs: $totalMs,
                success: false,
                error: $e->getMessage(),
            );
        }
    }

    public function name(): string
    {
        $scheme = in_array($this->scheme, ['tls', 'rediss', 'tls6', 'ssl'], true) ? 'rediss' : 'redis';

        return "{$scheme}://{$this->host}:{$this->port}";
    }
}
