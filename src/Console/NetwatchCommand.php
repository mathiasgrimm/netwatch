<?php

declare(strict_types=1);

namespace Mathiasgrimm\Netwatch\Console;

use Mathiasgrimm\Netwatch\Netwatch;
use Mathiasgrimm\Netwatch\Result\AggregateResult;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Throwable;

class NetwatchCommand extends Command
{
    protected static $defaultName = 'netwatch:run';

    protected function configure(): void
    {
        $this
            ->setName('netwatch:run')
            ->setDescription('Run network probes and display latency statistics')
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Path to config file', 'netwatch.php')
            ->addOption('iterations', 'i', InputOption::VALUE_REQUIRED, 'Override number of iterations')
            ->addOption('probe', 'p', InputOption::VALUE_REQUIRED, 'Run only a specific probe by name')
            ->addOption('sequential', null, InputOption::VALUE_NONE, 'Run probes sequentially instead of in parallel')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Output results as JSON')
            ->addOption('without-results', null, InputOption::VALUE_NONE, 'Exclude individual iteration results from JSON output');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configPath = $input->getOption('config');
        $iterations = $input->getOption('iterations') ? (int) $input->getOption('iterations') : null;
        $probeName = $input->getOption('probe');
        $jsonOutput = $input->getOption('json');
        $sequential = $input->getOption('sequential');
        $withoutResults = $input->getOption('without-results');

        if (! $sequential && ! $probeName) {
            return $this->executeParallel($input, $output, $configPath, $iterations, $jsonOutput, $withoutResults);
        }

        try {
            $netwatch = $this->resolveNetwatch($configPath);
            $results = $netwatch->run($probeName, $iterations);
        } catch (Throwable $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");

            return Command::FAILURE;
        }

        return $this->outputResults($output, $results, $jsonOutput, $withoutResults);
    }

    private function resolveNetwatch(string $configPath): Netwatch
    {
        // In Laravel context, resolve from the container
        if (function_exists('app') && app()->bound(Netwatch::class)) {
            return app(Netwatch::class);
        }

        return Netwatch::fromConfig($configPath);
    }

    private function executeParallel(
        InputInterface $input,
        OutputInterface $output,
        string $configPath,
        ?int $iterations,
        bool $jsonOutput,
        bool $withoutResults = false,
    ): int {
        // Resolve absolute config path for subprocesses
        if (! str_starts_with($configPath, '/')) {
            $configPath = getcwd().'/'.$configPath;
        }

        // Load config to discover probe names
        try {
            $netwatch = Netwatch::fromConfig($configPath);
            $probeNames = $netwatch->probeNames();
        } catch (Throwable $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");

            return Command::FAILURE;
        }

        $scriptPath = realpath($_SERVER['argv'][0]) ?: $_SERVER['argv'][0];

        // Start one subprocess per probe
        $processes = [];
        foreach ($probeNames as $name) {
            $cmd = [PHP_BINARY, $scriptPath, '--config', $configPath, '--probe', $name, '--json'];
            if ($iterations !== null) {
                $cmd[] = '--iterations';
                $cmd[] = (string) $iterations;
            }

            $process = new Process($cmd);
            $process->setTimeout(300);
            $process->start();
            $processes[$name] = $process;
        }

        // Collect results
        $results = [];
        $errors = [];

        foreach ($processes as $name => $process) {
            $process->wait();

            if (! $process->isSuccessful()) {
                $errors[$name] = trim($process->getErrorOutput() ?: $process->getOutput());

                continue;
            }

            $data = json_decode($process->getOutput(), true);
            if (! is_array($data)) {
                $errors[$name] = 'Failed to parse JSON output';

                continue;
            }

            foreach ($data as $probeName => $probeData) {
                $results[$probeName] = new AggregateResult(
                    name: $probeData['name'],
                    iterations: $probeData['iterations'],
                    stats: $probeData['stats'],
                    failures: $probeData['failures'],
                    results: [],
                );
            }
        }

        foreach ($errors as $name => $error) {
            $output->writeln("<error>Probe '{$name}' failed: {$error}</error>");
        }

        if (empty($results)) {
            $output->writeln('<error>All probes failed.</error>');

            return Command::FAILURE;
        }

        return $this->outputResults($output, $results, $jsonOutput, $withoutResults);
    }

    /**
     * @param  array<string, AggregateResult>  $results
     */
    private function outputResults(OutputInterface $output, array $results, bool $jsonOutput, bool $withoutResults = false): int
    {
        if ($jsonOutput) {
            $data = array_map(fn (AggregateResult $r) => $r->toArray($withoutResults), $results);
            $output->writeln(json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return Command::SUCCESS;
        }

        $this->renderTable($output, $results);

        return Command::SUCCESS;
    }

    /**
     * @param  array<string, AggregateResult>  $results
     */
    private function renderTable(OutputInterface $output, array $results): void
    {
        $table = new Table($output);
        $table->setHeaders([
            'Probe',
            'Iterations',
            'Failures',
            'Metric',
            'Min (ms)',
            'Max (ms)',
            'Avg (ms)',
            'P50 (ms)',
            'P95 (ms)',
            'P99 (ms)',
        ]);

        $names = array_keys($results);
        $last = end($names);

        foreach ($results as $name => $result) {
            $first = true;
            foreach (['connect_ms', 'request_ms', 'total_ms'] as $metric) {
                $stats = $result->stats[$metric];
                $label = match ($metric) {
                    'connect_ms' => 'connect',
                    'request_ms' => 'request',
                    'total_ms' => 'total',
                };

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
