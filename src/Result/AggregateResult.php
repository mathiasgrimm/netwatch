<?php

declare(strict_types=1);

namespace Mathiasgrimm\Netwatch\Result;

class AggregateResult
{
    public function __construct(
        public readonly string $name,
        public readonly int $iterations,
        public readonly array $stats,
        public readonly int $failures,
        public readonly array $results,
    ) {}

    public function toArray(bool $withoutResults = false): array
    {
        $data = [
            'name' => $this->name,
            'iterations' => $this->iterations,
            'stats' => $this->stats,
            'failures' => $this->failures,
        ];

        if (! $withoutResults) {
            $data['results'] = array_map(fn (ProbeResult $r) => $r->toArray(), $this->results);
        }

        return $data;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}
