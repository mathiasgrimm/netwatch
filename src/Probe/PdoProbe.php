<?php

declare(strict_types=1);

namespace Mathiasgrimm\Netwatch\Probe;

use Mathiasgrimm\Netwatch\Contract\ProbeInterface;
use Mathiasgrimm\Netwatch\Result\ProbeResult;
use PDO;
use Throwable;

class PdoProbe implements ProbeInterface
{
    public function __construct(
        private readonly string $dsn,
        private readonly ?string $username = null,
        private readonly ?string $password = null,
        private readonly array $options = [],
    ) {}

    public function probe(): ProbeResult
    {
        try {
            $start = hrtime(true);

            $pdo = new PDO($this->dsn, $this->username, $this->password, array_replace([
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 3,
            ], $this->options));

            $connectNs = hrtime(true) - $start;
            $connectMs = $connectNs / 1_000_000;

            $requestStart = hrtime(true);
            $pdo->query('SELECT 1');
            $requestNs = hrtime(true) - $requestStart;
            $requestMs = $requestNs / 1_000_000;

            return new ProbeResult(
                connectMs: $connectMs,
                requestMs: $requestMs,
                totalMs: $connectMs + $requestMs,
                success: true,
            );
        } catch (Throwable $e) {
            $elapsed = (hrtime(true) - $start) / 1_000_000;

            return new ProbeResult(
                connectMs: $elapsed,
                requestMs: 0,
                totalMs: $elapsed,
                success: false,
                error: $e->getMessage(),
            );
        }
    }

    public function name(): string
    {
        return "pdo:{$this->dsn}";
    }
}
