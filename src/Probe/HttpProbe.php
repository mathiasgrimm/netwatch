<?php

declare(strict_types=1);

namespace Mathiasgrimm\Netwatch\Probe;

use Mathiasgrimm\Netwatch\Contract\ProbeInterface;
use Mathiasgrimm\Netwatch\Result\ProbeResult;

class HttpProbe implements ProbeInterface
{
    public function __construct(
        private readonly string $url,
        private readonly string $method = 'GET',
        private readonly array $headers = [],
        private readonly float $timeout = 3.0,
    ) {}

    public function probe(): ProbeResult
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $this->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT_MS => (int) ($this->timeout * 1000),
            CURLOPT_CONNECTTIMEOUT_MS => (int) ($this->timeout * 1000),
            CURLOPT_CUSTOMREQUEST => $this->method,
            CURLOPT_NOBODY => $this->method === 'HEAD',
            CURLOPT_HTTPHEADER => $this->headers,
            CURLOPT_FRESH_CONNECT => true,
            CURLOPT_FORBID_REUSE => true,
        ]);

        $result = curl_exec($ch);
        $errno = curl_errno($ch);

        if ($errno !== 0) {
            $error = curl_error($ch);
            $connectMs = curl_getinfo($ch, CURLINFO_CONNECT_TIME) * 1000;
            $totalMs = curl_getinfo($ch, CURLINFO_TOTAL_TIME) * 1000;
            curl_close($ch);

            return new ProbeResult(
                connectMs: $connectMs,
                requestMs: max(0, $totalMs - $connectMs),
                totalMs: $totalMs,
                success: false,
                error: "curl error [$errno]: $error",
            );
        }

        $connectMs = curl_getinfo($ch, CURLINFO_CONNECT_TIME) * 1000;
        $totalMs = curl_getinfo($ch, CURLINFO_TOTAL_TIME) * 1000;
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $requestMs = max(0, $totalMs - $connectMs);
        $success = $httpCode >= 200 && $httpCode < 400;

        return new ProbeResult(
            connectMs: $connectMs,
            requestMs: $requestMs,
            totalMs: $totalMs,
            success: $success,
            error: $success ? null : "HTTP $httpCode",
        );
    }

    public function name(): string
    {
        return "http:{$this->url}";
    }
}
