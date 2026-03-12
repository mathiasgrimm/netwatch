<?php

declare(strict_types=1);

use Mathiasgrimm\Netwatch\Result\AggregateResult;
use Mathiasgrimm\Netwatch\Result\ProbeResult;

test('constructor sets properties', function () {
    $results = [
        new ProbeResult(1.0, 2.0, 3.0, true),
        new ProbeResult(1.5, 2.5, 4.0, true),
    ];

    $stats = [
        'connect_ms' => ['min' => 1.0, 'max' => 1.5, 'avg' => 1.25, 'p50' => 1.25, 'p95' => 1.475, 'p99' => 1.495],
        'request_ms' => ['min' => 2.0, 'max' => 2.5, 'avg' => 2.25, 'p50' => 2.25, 'p95' => 2.475, 'p99' => 2.495],
        'total_ms' => ['min' => 3.0, 'max' => 4.0, 'avg' => 3.5, 'p50' => 3.5, 'p95' => 3.95, 'p99' => 3.99],
    ];

    $aggregate = new AggregateResult('test-probe', 2, $stats, 0, $results);

    expect($aggregate->name)->toBe('test-probe')
        ->and($aggregate->iterations)->toBe(2)
        ->and($aggregate->stats)->toBe($stats)
        ->and($aggregate->failures)->toBe(0)
        ->and($aggregate->results)->toHaveCount(2);
});

test('toArray includes all fields', function () {
    $stats = [
        'connect_ms' => ['min' => 1.0, 'max' => 1.0, 'avg' => 1.0, 'p50' => 1.0, 'p95' => 1.0, 'p99' => 1.0],
        'request_ms' => ['min' => 2.0, 'max' => 2.0, 'avg' => 2.0, 'p50' => 2.0, 'p95' => 2.0, 'p99' => 2.0],
        'total_ms' => ['min' => 3.0, 'max' => 3.0, 'avg' => 3.0, 'p50' => 3.0, 'p95' => 3.0, 'p99' => 3.0],
    ];

    $aggregate = new AggregateResult('test', 1, $stats, 0, [
        new ProbeResult(1.0, 2.0, 3.0, true),
    ]);

    $array = $aggregate->toArray();

    expect($array['name'])->toBe('test')
        ->and($array['iterations'])->toBe(1)
        ->and($array['stats'])->toBe($stats)
        ->and($array['failures'])->toBe(0)
        ->and($array['results'])->toHaveCount(1)
        ->and($array['results'][0]['connect_ms'])->toBe(1.0);
});

test('toJson produces valid json', function () {
    $aggregate = new AggregateResult(
        name: 'test',
        iterations: 1,
        stats: [
            'connect_ms' => ['min' => 1.0, 'max' => 1.0, 'avg' => 1.0, 'p50' => 1.0, 'p95' => 1.0, 'p99' => 1.0],
            'request_ms' => ['min' => 0, 'max' => 0, 'avg' => 0, 'p50' => 0, 'p95' => 0, 'p99' => 0],
            'total_ms' => ['min' => 1.0, 'max' => 1.0, 'avg' => 1.0, 'p50' => 1.0, 'p95' => 1.0, 'p99' => 1.0],
        ],
        failures: 0,
        results: [],
    );

    $decoded = json_decode($aggregate->toJson(), true);

    expect($decoded['name'])->toBe('test')
        ->and($decoded['iterations'])->toBe(1)
        ->and($decoded['stats'])->toHaveKey('connect_ms');
});

test('toArray includes failures', function () {
    $aggregate = new AggregateResult(
        name: 'failing',
        iterations: 3,
        stats: [
            'connect_ms' => ['min' => 0, 'max' => 0, 'avg' => 0, 'p50' => 0, 'p95' => 0, 'p99' => 0],
            'request_ms' => ['min' => 0, 'max' => 0, 'avg' => 0, 'p50' => 0, 'p95' => 0, 'p99' => 0],
            'total_ms' => ['min' => 0, 'max' => 0, 'avg' => 0, 'p50' => 0, 'p95' => 0, 'p99' => 0],
        ],
        failures: 3,
        results: [
            new ProbeResult(10.0, 0, 10.0, false, 'fail 1'),
            new ProbeResult(20.0, 0, 20.0, false, 'fail 2'),
            new ProbeResult(30.0, 0, 30.0, false, 'fail 3'),
        ],
    );

    $array = $aggregate->toArray();

    expect($array['failures'])->toBe(3)
        ->and($array['results'][0]['success'])->toBeFalse()
        ->and($array['results'][0]['error'])->toBe('fail 1');
});
