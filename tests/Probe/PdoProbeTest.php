<?php

declare(strict_types=1);

use Mathiasgrimm\Netwatch\Probe\PdoProbe;

test('name for mysql', function () {
    $probe = new PdoProbe('mysql:host=127.0.0.1;dbname=app', 'root', '');
    expect($probe->name())->toBe('pdo:mysql:host=127.0.0.1;dbname=app');
});

test('name for postgres', function () {
    $probe = new PdoProbe('pgsql:host=localhost;dbname=test', 'user', 'pass');
    expect($probe->name())->toBe('pdo:pgsql:host=localhost;dbname=test');
});

test('probe succeeds with sqlite in-memory', function () {
    $probe = new PdoProbe('sqlite::memory:');
    $result = $probe->probe();

    expect($result->success)->toBeTrue()
        ->and($result->error)->toBeNull()
        ->and($result->connectMs)->toBeGreaterThan(0)
        ->and($result->requestMs)->toBeGreaterThan(0)
        ->and($result->totalMs)->toBeGreaterThan(0);
});

test('probe fails on bad dsn', function () {
    $probe = new PdoProbe('mysql:host=192.0.2.1;port=9999', 'bad', 'bad', [
        PDO::ATTR_TIMEOUT => 1,
    ]);
    $result = $probe->probe();

    expect($result->success)->toBeFalse()
        ->and($result->error)->not->toBeNull()
        ->and($result->totalMs)->toBeGreaterThan(0);
});

test('probe succeeds on local mysql', function () {
    $socket = @fsockopen('127.0.0.1', 3306, $errno, $errstr, 0.5);
    if (!$socket) {
        $this->markTestSkipped('MySQL not available on localhost:3306');
    }
    fclose($socket);

    $probe = new PdoProbe('mysql:host=127.0.0.1;port=3306', 'root', '');
    $result = $probe->probe();

    expect($result->success)->toBeTrue()
        ->and($result->connectMs)->toBeGreaterThan(0)
        ->and($result->requestMs)->toBeGreaterThan(0);
});
