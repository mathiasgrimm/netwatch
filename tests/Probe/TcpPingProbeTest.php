<?php

declare(strict_types=1);

use Mathiasgrimm\Netwatch\Probe\TcpPingProbe;

test('name returns tcp uri', function () {
    $probe = new TcpPingProbe('example.com', 443);
    expect($probe->name())->toBe('tcp://example.com:443');
});

test('probe fails on unreachable host', function () {
    $probe = new TcpPingProbe('192.0.2.1', 9999, timeout: 0.5);
    $result = $probe->probe();

    expect($result->success)->toBeFalse()
        ->and($result->error)->not->toBeNull()
        ->and($result->connectMs)->toBeGreaterThan(0)
        ->and($result->requestMs)->toBe(0.0);
});

test('probe succeeds on reachable host', function () {
    $probe = new TcpPingProbe('1.1.1.1', 443, timeout: 5.0);
    $result = $probe->probe();

    expect($result->success)->toBeTrue()
        ->and($result->error)->toBeNull()
        ->and($result->connectMs)->toBeGreaterThan(0)
        ->and($result->requestMs)->toBe(0.0)
        ->and($result->totalMs)->toBe($result->connectMs);
});

test('result has correct structure', function () {
    $probe = new TcpPingProbe('1.1.1.1', 443);
    $array = $probe->probe()->toArray();

    expect($array)->toHaveKeys(['connect_ms', 'request_ms', 'total_ms', 'success', 'error']);
});
