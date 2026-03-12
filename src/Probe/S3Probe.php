<?php

declare(strict_types=1);

namespace Mathiasgrimm\Netwatch\Probe;

use Mathiasgrimm\Netwatch\Contract\ProbeInterface;
use Mathiasgrimm\Netwatch\Result\ProbeResult;

class S3Probe implements ProbeInterface
{
    public function __construct(
        private readonly string $bucket,
        private readonly string $region = 'us-east-1',
        private readonly string $key = '',
        private readonly string $secret = '',
        private readonly ?string $endpoint = null,
        private readonly float $timeout = 3.0,
    ) {}

    public function probe(): ProbeResult
    {
        $host = $this->endpoint
            ? parse_url($this->endpoint, PHP_URL_HOST)
            : "{$this->bucket}.s3.{$this->region}.amazonaws.com";

        $scheme = $this->endpoint
            ? parse_url($this->endpoint, PHP_URL_SCHEME) ?? 'https'
            : 'https';

        $url = "{$scheme}://{$host}/";
        $now = gmdate('Ymd\THis\Z');
        $date = gmdate('Ymd');
        $service = 's3';

        $headers = [
            'host' => $host,
            'x-amz-date' => $now,
        ];

        // Build canonical request for HeadBucket
        $signedHeaders = 'host;x-amz-date';
        $canonicalHeaders = "host:{$host}\nx-amz-date:{$now}\n";
        $payloadHash = hash('sha256', '');

        $canonicalRequest = implode("\n", [
            'HEAD',
            '/',
            '',
            $canonicalHeaders,
            $signedHeaders,
            $payloadHash,
        ]);

        $credentialScope = "{$date}/{$this->region}/{$service}/aws4_request";
        $stringToSign = implode("\n", [
            'AWS4-HMAC-SHA256',
            $now,
            $credentialScope,
            hash('sha256', $canonicalRequest),
        ]);

        $signingKey = hash_hmac('sha256', 'aws4_request',
            hash_hmac('sha256', $service,
                hash_hmac('sha256', $this->region,
                    hash_hmac('sha256', $date, "AWS4{$this->secret}", true),
                    true),
                true),
            true);

        $signature = hash_hmac('sha256', $stringToSign, $signingKey);
        $authorization = "AWS4-HMAC-SHA256 Credential={$this->key}/{$credentialScope}, SignedHeaders={$signedHeaders}, Signature={$signature}";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY => true,
            CURLOPT_TIMEOUT_MS => (int) ($this->timeout * 1000),
            CURLOPT_CONNECTTIMEOUT_MS => (int) ($this->timeout * 1000),
            CURLOPT_HTTPHEADER => [
                "Host: {$host}",
                "X-Amz-Date: {$now}",
                "X-Amz-Content-Sha256: {$payloadHash}",
                "Authorization: {$authorization}",
            ],
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
            error: $success ? null : "S3 HeadBucket HTTP $httpCode",
        );
    }

    public function name(): string
    {
        return "s3://{$this->bucket}";
    }
}
