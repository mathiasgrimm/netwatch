<?php

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Mathiasgrimm\Netwatch\Probe\HttpProbe;

function createHttpFactory(string $url, int $status): Factory
{
    $factory = new Factory;
    $factory->fake([$url => Factory::response('', $status)]);

    return $factory;
}

test('name returns http uri', function () {
    $probe = new HttpProbe('https://example.com/health');
    expect($probe->name())->toBe('http:https://example.com/health');
});

test('probe succeeds on valid url', function () {
    $factory = createHttpFactory('https://example.com', 200);

    $probe = new HttpProbe('https://example.com', httpClientFactory: $factory);
    $result = $probe->probe();

    expect($result->success)->toBeTrue()
        ->and($result->error)->toBeNull()
        ->and($result->totalMs)->toBeGreaterThanOrEqual(0);
});

test('probe fails on 5xx', function () {
    $factory = createHttpFactory('https://example.com', 500);

    $probe = new HttpProbe('https://example.com', httpClientFactory: $factory);
    $result = $probe->probe();

    expect($result->success)->toBeFalse()
        ->and($result->error)->toBe('HTTP 500');
});

test('probe fails on unreachable host', function () {
    $factory = new Factory;
    $factory->fake(['*' => fn () => throw new ConnectionException('Connection refused')]);

    $probe = new HttpProbe('http://192.0.2.1:9999', timeout: 0.5, httpClientFactory: $factory);
    $result = $probe->probe();

    expect($result->success)->toBeFalse()
        ->and($result->error)->toContain('Connection refused');
});

test('probe succeeds when expectedCode matches actual response code', function () {
    $factory = createHttpFactory('https://example.com', 421);

    $probe = new HttpProbe('https://example.com', expectedCode: 421, httpClientFactory: $factory);
    $result = $probe->probe();

    expect($result->success)->toBeTrue()
        ->and($result->error)->toBeNull();
});

test('probe fails when expectedCode does not match actual response code', function () {
    $factory = createHttpFactory('https://example.com', 421);

    $probe = new HttpProbe('https://example.com', expectedCode: 200, httpClientFactory: $factory);
    $result = $probe->probe();

    expect($result->success)->toBeFalse()
        ->and($result->error)->toBe('HTTP 421');
});

test('connect and request times are separated', function () {
    $factory = createHttpFactory('https://example.com', 200);

    $probe = new HttpProbe('https://example.com', httpClientFactory: $factory);
    $result = $probe->probe();

    expect($result->connectMs)->toBeGreaterThanOrEqual(0)
        ->and($result->requestMs)->toBeGreaterThanOrEqual(0)
        ->and($result->totalMs)->toBeGreaterThanOrEqual(0);
});
