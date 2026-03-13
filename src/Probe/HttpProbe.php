<?php

declare(strict_types=1);

namespace Mathiasgrimm\Netwatch\Probe;

use GuzzleHttp\TransferStats;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\RequestException;
use Mathiasgrimm\Netwatch\Contract\ProbeInterface;
use Mathiasgrimm\Netwatch\Result\ProbeResult;

class HttpProbe implements ProbeInterface
{
    private readonly Factory $httpClientFactory;

    public function __construct(
        private readonly string $url,
        private readonly string $method = 'GET',
        private readonly array $headers = [],
        private readonly float $timeout = 3.0,
        private readonly ?int $expectedCode = null,
        ?Factory $httpClientFactory = null,
    ) {
        $this->httpClientFactory = $httpClientFactory ?? new Factory;
    }

    public function probe(): ProbeResult
    {
        $connectMs = 0.0;
        $totalMs = 0.0;

        try {
            $response = $this->httpClientFactory
                ->timeout((int) $this->timeout)
                ->withHeaders($this->headers)
                ->withOptions(['on_stats' => function (TransferStats $stats) use (&$connectMs, &$totalMs) {
                    $handlerStats = $stats->getHandlerStats();
                    $connectMs = ($handlerStats['connect_time'] ?? 0) * 1000;
                    $totalMs = ($stats->getTransferTime() ?? 0) * 1000;
                }])
                ->send($this->method, $this->url)
                ->throw();

            $httpCode = $response->status();
        } catch (RequestException $e) {
            $httpCode = $e->response->status();
        } catch (\Exception $e) {
            return new ProbeResult(
                connectMs: $connectMs,
                requestMs: max(0, $totalMs - $connectMs),
                totalMs: $totalMs,
                success: false,
                error: $e->getMessage(),
            );
        }

        $requestMs = max(0, $totalMs - $connectMs);
        $success = $this->expectedCode !== null
            ? $httpCode === $this->expectedCode
            : $httpCode >= 200 && $httpCode < 400;

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
