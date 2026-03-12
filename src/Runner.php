<?php

declare(strict_types=1);

namespace Mathiasgrimm\Netwatch;

use Mathiasgrimm\Netwatch\Contract\ProbeInterface;
use Mathiasgrimm\Netwatch\Result\AggregateResult;
use Mathiasgrimm\Netwatch\Result\ProbeResult;

class Runner
{
    public function run(ProbeInterface $probe, int $iterations, int $failThreshold = 3): AggregateResult
    {
        $results = [];
        $failures = 0;
        $consecutiveFailures = 0;

        for ($i = 0; $i < $iterations; $i++) {
            $result = $probe->probe();
            $results[] = $result;

            if (!$result->success) {
                $failures++;
                $consecutiveFailures++;

                if ($consecutiveFailures >= $failThreshold) {
                    break;
                }
            } else {
                $consecutiveFailures = 0;
            }
        }

        $stats = $this->computeStats($results);

        return new AggregateResult(
            name: $probe->name(),
            iterations: $iterations,
            stats: $stats,
            failures: $failures,
            results: $results,
        );
    }

    private function computeStats(array $results): array
    {
        $successful = array_filter($results, fn (ProbeResult $r) => $r->success);

        if (empty($successful)) {
            return [
                'connect_ms' => ['min' => 0, 'max' => 0, 'avg' => 0, 'p50' => 0, 'p95' => 0, 'p99' => 0],
                'request_ms' => ['min' => 0, 'max' => 0, 'avg' => 0, 'p50' => 0, 'p95' => 0, 'p99' => 0],
                'total_ms' => ['min' => 0, 'max' => 0, 'avg' => 0, 'p50' => 0, 'p95' => 0, 'p99' => 0],
            ];
        }

        $successful = array_values($successful);

        return [
            'connect_ms' => $this->computeMetricStats($successful, 'connectMs'),
            'request_ms' => $this->computeMetricStats($successful, 'requestMs'),
            'total_ms' => $this->computeMetricStats($successful, 'totalMs'),
        ];
    }

    private function computeMetricStats(array $results, string $property): array
    {
        $values = array_map(fn (ProbeResult $r) => $r->$property, $results);
        sort($values);

        $count = count($values);
        $sum = array_sum($values);

        return [
            'min' => round($values[0], 3),
            'max' => round($values[$count - 1], 3),
            'avg' => round($sum / $count, 3),
            'p50' => round($this->percentile($values, 50), 3),
            'p95' => round($this->percentile($values, 95), 3),
            'p99' => round($this->percentile($values, 99), 3),
        ];
    }

    private function percentile(array $sorted, float $percentile): float
    {
        $count = count($sorted);

        if ($count === 1) {
            return $sorted[0];
        }

        $index = ($percentile / 100) * ($count - 1);
        $lower = (int) floor($index);
        $upper = (int) ceil($index);
        $fraction = $index - $lower;

        if ($lower === $upper) {
            return $sorted[$lower];
        }

        return $sorted[$lower] + $fraction * ($sorted[$upper] - $sorted[$lower]);
    }
}
