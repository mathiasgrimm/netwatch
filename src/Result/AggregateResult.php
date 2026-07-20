<?php

declare(strict_types=1);

namespace MathiasGrimm\Netwatch\Result;

class AggregateResult
{
    /**
     * @param  array{warn: float|null, crit: float|null}|null  $thresholds  Latency thresholds (ms) checked against total p95
     */
    public function __construct(
        public readonly string $name,
        public readonly int $iterations,
        public readonly array $stats,
        public readonly int $failures,
        public readonly array $results,
        public readonly ?array $thresholds = null,
    ) {}

    /**
     * Evaluate the probe outcome: 'failing' when any iteration failed,
     * otherwise total p95 against the crit/warn thresholds, 'ok' when neither
     * threshold is set or breached. Individual samples are evaluated
     * separately via sampleStatus()/overWarnCount()/overCritCount().
     */
    public function status(): string
    {
        if ($this->failures > 0) {
            return 'failing';
        }

        $p95 = $this->stats['total_ms']['p95'] ?? null;
        $warn = $this->thresholds['warn'] ?? null;
        $crit = $this->thresholds['crit'] ?? null;

        if ($p95 !== null && $crit !== null && $p95 >= $crit) {
            return 'crit';
        }

        if ($p95 !== null && $warn !== null && $p95 >= $warn) {
            return 'warn';
        }

        return 'ok';
    }

    /**
     * Evaluate one iteration against the thresholds.
     */
    public function sampleStatus(ProbeResult $result): string
    {
        if (! $result->success) {
            return 'failing';
        }

        $warn = $this->thresholds['warn'] ?? null;
        $crit = $this->thresholds['crit'] ?? null;

        if ($crit !== null && $result->totalMs >= $crit) {
            return 'crit';
        }

        if ($warn !== null && $result->totalMs >= $warn) {
            return 'warn';
        }

        return 'ok';
    }

    /**
     * Successful iterations with total latency at or over the warn threshold
     * (includes those also over crit).
     */
    public function overWarnCount(): int
    {
        $warn = $this->thresholds['warn'] ?? null;
        if ($warn === null) {
            return 0;
        }

        return count(array_filter($this->results, fn (ProbeResult $r) => $r->success && $r->totalMs >= $warn));
    }

    /**
     * Successful iterations with total latency at or over the crit threshold.
     */
    public function overCritCount(): int
    {
        $crit = $this->thresholds['crit'] ?? null;
        if ($crit === null) {
            return 0;
        }

        return count(array_filter($this->results, fn (ProbeResult $r) => $r->success && $r->totalMs >= $crit));
    }

    public function toArray(bool $withoutResults = false): array
    {
        $data = [
            'name' => $this->name,
            'iterations' => $this->iterations,
            'stats' => $this->stats,
            'failures' => $this->failures,
            'status' => $this->status(),
            'thresholds' => [
                'warn' => $this->thresholds['warn'] ?? null,
                'crit' => $this->thresholds['crit'] ?? null,
            ],
            'over_warn' => $this->overWarnCount(),
            'over_crit' => $this->overCritCount(),
        ];

        if (! $withoutResults) {
            $data['results'] = array_map(
                fn (ProbeResult $r) => $r->toArray() + ['status' => $this->sampleStatus($r)],
                $this->results,
            );
        }

        return $data;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}
