<?php

declare(strict_types=1);

use MathiasGrimm\Netwatch\Probe\PhpRedisProbe;

test('name with tcp scheme and port', function () {
    $probe = new PhpRedisProbe('tcp://redis.local:6380');
    expect($probe->name())->toBe('redis://redis.local:6380');
});

test('name with default port', function () {
    $probe = new PhpRedisProbe('tcp://redis.local');
    expect($probe->name())->toBe('redis://redis.local:6379');
});

test('empty-string username and password are treated as absent', function () {
    $probe = new PhpRedisProbe('tcp://redis.local:6379', username: '', password: '');

    $read = fn (string $prop) => (function () use ($prop) {
        return $this->{$prop};
    })->call($probe);

    expect($read('username'))->toBeNull()
        ->and($read('password'))->toBeNull();
});

test('non-empty username and password are preserved', function () {
    $probe = new PhpRedisProbe('tcp://redis.local:6379', username: 'alice', password: 'secret');

    $read = fn (string $prop) => (function () use ($prop) {
        return $this->{$prop};
    })->call($probe);

    expect($read('username'))->toBe('alice')
        ->and($read('password'))->toBe('secret');
});

test('probe fails on unreachable host', function () {
    $probe = new PhpRedisProbe('tcp://192.0.2.1:9999', timeout: 0.5);
    $result = $probe->probe();

    expect($result->success)->toBeFalse()
        ->and($result->error)->not->toBeNull();
});

test('probe succeeds on local redis', function () {
    $socket = @fsockopen('127.0.0.1', 6379, $errno, $errstr, 0.5);
    if (! $socket) {
        $this->markTestSkipped('Redis not available on localhost:6379');
    }
    fclose($socket);

    $probe = new PhpRedisProbe('tcp://127.0.0.1:6379');
    $result = $probe->probe();

    expect($result->success)->toBeTrue()
        ->and($result->error)->toBeNull()
        ->and($result->connectMs)->toBeGreaterThan(0)
        ->and($result->requestMs)->toBeGreaterThan(0)
        ->and($result->totalMs)->toBeGreaterThan(0);
});
