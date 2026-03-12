<?php

declare(strict_types=1);

namespace Mathiasgrimm\Netwatch\Laravel\Console;

use Illuminate\Console\Command;
use Mathiasgrimm\Netwatch\Netwatch;
use Mathiasgrimm\Netwatch\Result\AggregateResult;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;

class NetwatchCommand extends Command
{
    protected $signature = 'netwatch:run
        {--iterations= : Override number of iterations}
        {--probe= : Run only a specific probe by name}
        {--json : Output results as JSON}
        {--without-results : Exclude individual iteration results from JSON output}';

    protected $description = 'Run network probes and display latency statistics';

    public function handle(Netwatch $netwatch): int
    {
        $iterations = $this->option('iterations') ? (int) $this->option('iterations') : null;
        $probeName = $this->option('probe');

        try {
            $results = $netwatch->run($probeName, $iterations);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($this->option('json')) {
            $withoutResults = (bool) $this->option('without-results');
            $data = array_map(fn (AggregateResult $r) => $r->toArray($withoutResults), $results);
            $this->line(json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->renderTable($results);

        return self::SUCCESS;
    }

    /**
     * @param  array<string, AggregateResult>  $results
     */
    private function renderTable(array $results): void
    {
        $table = new Table($this->output);
        $table->setHeaders(['Probe', 'Iterations', 'Failures', 'Metric', 'Min (ms)', 'Max (ms)', 'Avg (ms)', 'P50 (ms)', 'P95 (ms)', 'P99 (ms)']);

        $names = array_keys($results);
        $last = end($names);

        foreach ($results as $name => $result) {
            $first = true;
            foreach (['connect_ms' => 'connect', 'request_ms' => 'request', 'total_ms' => 'total'] as $metric => $label) {
                $stats = $result->stats[$metric];
                $table->addRow([
                    $first ? $name : '',
                    $first ? $result->iterations : '',
                    $first ? $result->failures : '',
                    $label,
                    $stats['min'],
                    $stats['max'],
                    $stats['avg'],
                    $stats['p50'],
                    $stats['p95'],
                    $stats['p99'],
                ]);
                $first = false;
            }

            if ($name !== $last) {
                $table->addRow(new TableSeparator);
            }
        }

        $table->render();
    }
}
