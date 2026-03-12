<?php

declare(strict_types=1);

use Mathiasgrimm\Netwatch\Result\ProbeResult;

test('constructor sets properties', function () {
    $result = new ProbeResult(
        connectMs: 1.234,
        requestMs: 5.678,
        totalMs: 6.912,
        success: true,
    );

    expect($result->connectMs)->toBe(1.234)
        ->and($result->requestMs)->toBe(5.678)
        ->and($result->totalMs)->toBe(6.912)
        ->and($result->success)->toBeTrue()
        ->and($result->error)->toBeNull();
});

test('constructor with error', function () {
    $result = new ProbeResult(
        connectMs: 100.0,
        requestMs: 0,
        totalMs: 100.0,
        success: false,
        error: 'Connection refused',
    );

    expect($result->success)->toBeFalse()
        ->and($result->error)->toBe('Connection refused');
});

test('toArray rounds to three decimals', function () {
    $result = new ProbeResult(
        connectMs: 1.23456,
        requestMs: 5.67891,
        totalMs: 6.91347,
        success: true,
    );

    $array = $result->toArray();

    expect($array['connect_ms'])->toBe(1.235)
        ->and($array['request_ms'])->toBe(5.679)
        ->and($array['total_ms'])->toBe(6.913)
        ->and($array['success'])->toBeTrue()
        ->and($array['error'])->toBeNull();
});

test('toArray with error', function () {
    $result = new ProbeResult(
        connectMs: 10.0,
        requestMs: 0,
        totalMs: 10.0,
        success: false,
        error: 'Timeout',
    );

    $array = $result->toArray();

    expect($array['success'])->toBeFalse()
        ->and($array['error'])->toBe('Timeout');
});

test('toJson produces valid json', function () {
    $result = new ProbeResult(
        connectMs: 1.0,
        requestMs: 2.0,
        totalMs: 3.0,
        success: true,
    );

    $decoded = json_decode($result->toJson(), true);

    expect($decoded['connect_ms'])->toEqual(1.0)
        ->and($decoded['request_ms'])->toEqual(2.0)
        ->and($decoded['total_ms'])->toEqual(3.0)
        ->and($decoded['success'])->toBeTrue();
});
