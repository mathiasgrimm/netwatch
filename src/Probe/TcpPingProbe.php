<?php

declare(strict_types=1);

namespace Mathiasgrimm\Netwatch\Probe;

use Mathiasgrimm\Netwatch\Contract\ProbeInterface;
use Mathiasgrimm\Netwatch\Result\ProbeResult;

class TcpPingProbe implements ProbeInterface
{
    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly float $timeout = 3.0,
    ) {}

    public function probe(): ProbeResult
    {
        $start = hrtime(true);

        $errno = 0;
        $errstr = '';
        $socket = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);

        $connectNs = hrtime(true) - $start;
        $connectMs = $connectNs / 1_000_000;

        if ($socket === false) {
            return new ProbeResult(
                connectMs: $connectMs,
                requestMs: 0,
                totalMs: $connectMs,
                success: false,
                error: "TCP connect failed: [$errno] $errstr",
            );
        }

        fclose($socket);

        return new ProbeResult(
            connectMs: $connectMs,
            requestMs: 0,
            totalMs: $connectMs,
            success: true,
        );
    }

    public function name(): string
    {
        return "tcp://{$this->host}:{$this->port}";
    }
}
