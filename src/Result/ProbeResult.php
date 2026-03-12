<?php

declare(strict_types=1);

namespace Mathiasgrimm\Netwatch\Result;

class ProbeResult
{
    public function __construct(
        public readonly float $connectMs,
        public readonly float $requestMs,
        public readonly float $totalMs,
        public readonly bool $success,
        public readonly ?string $error = null,
    ) {}

    public function toArray(): array
    {
        return [
            'connect_ms' => round($this->connectMs, 3),
            'request_ms' => round($this->requestMs, 3),
            'total_ms' => round($this->totalMs, 3),
            'success' => $this->success,
            'error' => $this->error,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}
