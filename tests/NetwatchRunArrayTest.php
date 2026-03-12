<?php

declare(strict_types=1);

use Mathiasgrimm\Netwatch\Netwatch;
use Mathiasgrimm\Netwatch\Tests\Fixtures\SuccessProbe;

test('run with array of probe names returns only those probes', function () {
    $netwatch = new Netwatch(
        probes: [
            'probe-a' => ['probe' => new SuccessProbe(), 'iterations' => 2],
            'probe-b' => ['probe' => new SuccessProbe(), 'iterations' => 2],
            'probe-c' => ['probe' => new SuccessProbe(), 'iterations' => 2],
        ],
        defaultIterations: 2,
    );

    $results = $netwatch->run(['probe-a', 'probe-b']);

    expect($results)->toHaveCount(2)
        ->toHaveKey('probe-a')
        ->toHaveKey('probe-b')
        ->not->toHaveKey('probe-c');
});

test('run with array containing nonexistent probe throws', function () {
    $netwatch = new Netwatch(
        probes: [
            'probe-a' => ['probe' => new SuccessProbe()],
        ],
        defaultIterations: 1,
    );

    $netwatch->run(['probe-a', 'nonexistent']);
})->throws(InvalidArgumentException::class, 'Probe not found: nonexistent');

test('run with null still runs all probes', function () {
    $netwatch = new Netwatch(
        probes: [
            'probe-a' => ['probe' => new SuccessProbe(), 'iterations' => 1],
            'probe-b' => ['probe' => new SuccessProbe(), 'iterations' => 1],
        ],
        defaultIterations: 1,
    );

    $results = $netwatch->run(null);

    expect($results)->toHaveCount(2)
        ->toHaveKey('probe-a')
        ->toHaveKey('probe-b');
});

test('run with string still works as before', function () {
    $netwatch = new Netwatch(
        probes: [
            'probe-a' => ['probe' => new SuccessProbe(), 'iterations' => 1],
            'probe-b' => ['probe' => new SuccessProbe(), 'iterations' => 1],
        ],
        defaultIterations: 1,
    );

    $results = $netwatch->run('probe-a');

    expect($results)->toHaveCount(1)->toHaveKey('probe-a');
});
