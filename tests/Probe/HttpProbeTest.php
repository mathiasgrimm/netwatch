<?php

declare(strict_types=1);

use Mathiasgrimm\Netwatch\Probe\HttpProbe;

test('name returns http uri', function () {
    $probe = new HttpProbe('https://example.com/health');
    expect($probe->name())->toBe('http:https://example.com/health');
});

test('probe succeeds on valid url', function () {
    $probe = new HttpProbe('https://httpbin.org/status/200');
    $result = $probe->probe();

    expect($result->success)->toBeTrue()
        ->and($result->error)->toBeNull()
        ->and($result->connectMs)->toBeGreaterThan(0)
        ->and($result->totalMs)->toBeGreaterThan(0);
});

test('probe fails on 5xx', function () {
    $probe = new HttpProbe('https://httpbin.org/status/500');
    $result = $probe->probe();

    expect($result->success)->toBeFalse()
        ->and($result->error)->toBe('HTTP 500');
});

test('probe fails on unreachable host', function () {
    $probe = new HttpProbe('http://192.0.2.1:9999', timeout: 0.5);
    $result = $probe->probe();

    expect($result->success)->toBeFalse()
        ->and($result->error)->toContain('curl error');
});

test('connect and request times are separated', function () {
    $probe = new HttpProbe('https://httpbin.org/status/200');
    $result = $probe->probe();

    expect($result->connectMs)->toBeGreaterThanOrEqual(0)
        ->and($result->requestMs)->toBeGreaterThanOrEqual(0)
        ->and($result->totalMs)->toBeGreaterThan(0);
});
