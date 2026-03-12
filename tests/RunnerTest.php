<?php

declare(strict_types=1);

use Mathiasgrimm\Netwatch\Contract\ProbeInterface;
use Mathiasgrimm\Netwatch\Result\ProbeResult;
use Mathiasgrimm\Netwatch\Runner;

function createFakeProbe(array $results): ProbeInterface
{
    return new class($results) implements ProbeInterface
    {
        private int $index = 0;

        public function __construct(private readonly array $results) {}

        public function probe(): ProbeResult
        {
            return $this->results[$this->index++];
        }

        public function name(): string
        {
            return 'fake-probe';
        }
    };
}

test('run returns aggregate result', function () {
    $probe = createFakeProbe([
        new ProbeResult(1.0, 2.0, 3.0, true),
        new ProbeResult(1.5, 2.5, 4.0, true),
    ]);

    $result = (new Runner)->run($probe, 2);

    expect($result->name)->toBe('fake-probe')
        ->and($result->iterations)->toBe(2)
        ->and($result->failures)->toBe(0)
        ->and($result->results)->toHaveCount(2);
});

test('stats with known values', function () {
    $results = [];
    for ($i = 1; $i <= 10; $i++) {
        $results[] = new ProbeResult((float) $i, (float) ($i * 2), (float) ($i * 3), true);
    }

    $aggregate = (new Runner)->run(createFakeProbe($results), 10);

    expect($aggregate->stats['connect_ms']['min'])->toBe(1.0)
        ->and($aggregate->stats['connect_ms']['max'])->toBe(10.0)
        ->and($aggregate->stats['connect_ms']['avg'])->toBe(5.5)
        ->and($aggregate->stats['connect_ms']['p50'])->toBe(5.5)
        ->and($aggregate->stats['request_ms']['min'])->toBe(2.0)
        ->and($aggregate->stats['request_ms']['max'])->toBe(20.0)
        ->and($aggregate->stats['request_ms']['avg'])->toBe(11.0)
        ->and($aggregate->stats['total_ms']['min'])->toBe(3.0)
        ->and($aggregate->stats['total_ms']['max'])->toBe(30.0)
        ->and($aggregate->stats['total_ms']['avg'])->toBe(16.5);
});

test('p95 and p99 with 100 values', function () {
    $results = [];
    for ($i = 1; $i <= 100; $i++) {
        $results[] = new ProbeResult((float) $i, 0, (float) $i, true);
    }

    $aggregate = (new Runner)->run(createFakeProbe($results), 100);

    expect($aggregate->stats['connect_ms']['min'])->toBe(1.0)
        ->and($aggregate->stats['connect_ms']['max'])->toBe(100.0)
        ->and($aggregate->stats['connect_ms']['avg'])->toBe(50.5)
        ->and($aggregate->stats['connect_ms']['p50'])->toBe(50.5)
        ->and($aggregate->stats['connect_ms']['p95'])->toBe(95.05)
        ->and($aggregate->stats['connect_ms']['p99'])->toBe(99.01);
});

test('single iteration', function () {
    $probe = createFakeProbe([
        new ProbeResult(5.0, 10.0, 15.0, true),
    ]);

    $aggregate = (new Runner)->run($probe, 1);
    $connect = $aggregate->stats['connect_ms'];

    expect($connect['min'])->toBe(5.0)
        ->and($connect['max'])->toBe(5.0)
        ->and($connect['avg'])->toBe(5.0)
        ->and($connect['p50'])->toBe(5.0)
        ->and($connect['p95'])->toBe(5.0)
        ->and($connect['p99'])->toBe(5.0);
});

test('failures are counted', function () {
    $probe = createFakeProbe([
        new ProbeResult(1.0, 2.0, 3.0, true),
        new ProbeResult(10.0, 0, 10.0, false, 'Connection refused'),
        new ProbeResult(1.5, 2.5, 4.0, true),
    ]);

    $aggregate = (new Runner)->run($probe, 3);

    expect($aggregate->failures)->toBe(1)
        ->and($aggregate->results)->toHaveCount(3);
});

test('stats exclude failures', function () {
    $probe = createFakeProbe([
        new ProbeResult(1.0, 2.0, 3.0, true),
        new ProbeResult(999.0, 0, 999.0, false, 'Timeout'),
        new ProbeResult(3.0, 4.0, 7.0, true),
    ]);

    $aggregate = (new Runner)->run($probe, 3);

    expect($aggregate->stats['connect_ms']['min'])->toBe(1.0)
        ->and($aggregate->stats['connect_ms']['max'])->toBe(3.0)
        ->and($aggregate->stats['connect_ms']['avg'])->toBe(2.0);
});

test('all failures returns zero stats', function () {
    $probe = createFakeProbe([
        new ProbeResult(10.0, 0, 10.0, false, 'fail'),
        new ProbeResult(20.0, 0, 20.0, false, 'fail'),
    ]);

    $aggregate = (new Runner)->run($probe, 2);

    expect($aggregate->failures)->toBe(2);

    foreach (['connect_ms', 'request_ms', 'total_ms'] as $metric) {
        foreach (['min', 'max', 'avg', 'p50', 'p95', 'p99'] as $stat) {
            expect($aggregate->stats[$metric][$stat])->toBe(0);
        }
    }
});

test('two iterations percentiles', function () {
    $probe = createFakeProbe([
        new ProbeResult(10.0, 0, 10.0, true),
        new ProbeResult(20.0, 0, 20.0, true),
    ]);

    $aggregate = (new Runner)->run($probe, 2);

    expect($aggregate->stats['connect_ms']['min'])->toBe(10.0)
        ->and($aggregate->stats['connect_ms']['max'])->toBe(20.0)
        ->and($aggregate->stats['connect_ms']['avg'])->toBe(15.0)
        ->and($aggregate->stats['connect_ms']['p50'])->toBe(15.0);
});

test('early bail on consecutive failures', function () {
    $probe = createFakeProbe([
        new ProbeResult(1.0, 0, 1.0, false, 'fail'),
        new ProbeResult(2.0, 0, 2.0, false, 'fail'),
        new ProbeResult(3.0, 0, 3.0, false, 'fail'),
        new ProbeResult(4.0, 0, 4.0, true), // should never be reached
    ]);

    $aggregate = (new Runner)->run($probe, 10, failThreshold: 3);

    expect($aggregate->results)->toHaveCount(3)
        ->and($aggregate->failures)->toBe(3);
});

test('consecutive failure counter resets on success', function () {
    $probe = createFakeProbe([
        new ProbeResult(1.0, 0, 1.0, false, 'fail'),
        new ProbeResult(2.0, 0, 2.0, false, 'fail'),
        new ProbeResult(3.0, 1.0, 4.0, true),          // resets counter
        new ProbeResult(4.0, 0, 4.0, false, 'fail'),
        new ProbeResult(5.0, 0, 5.0, false, 'fail'),
        new ProbeResult(6.0, 1.0, 7.0, true),          // resets counter again
    ]);

    $aggregate = (new Runner)->run($probe, 6, failThreshold: 3);

    expect($aggregate->results)->toHaveCount(6)
        ->and($aggregate->failures)->toBe(4);
});

test('custom fail threshold', function () {
    $probe = createFakeProbe([
        new ProbeResult(1.0, 0, 1.0, false, 'fail'),
        new ProbeResult(2.0, 0, 2.0, true), // should never be reached
    ]);

    $aggregate = (new Runner)->run($probe, 10, failThreshold: 1);

    expect($aggregate->results)->toHaveCount(1)
        ->and($aggregate->failures)->toBe(1);
});
