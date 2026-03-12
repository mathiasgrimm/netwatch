<?php

declare(strict_types=1);

namespace Mathiasgrimm\Netwatch;

use Mathiasgrimm\Netwatch\Contract\ProbeInterface;
use Mathiasgrimm\Netwatch\Result\AggregateResult;

class Netwatch
{
    private Runner $runner;

    private static ?\Closure $authUsing = null;

    public static function auth(\Closure $callback): void
    {
        static::$authUsing = $callback;
    }

    public static function authUsing(): ?\Closure
    {
        return static::$authUsing;
    }

    /**
     * @param  array<string, array{probe: ProbeInterface, iterations?: int}>  $probes
     */
    public function __construct(
        private readonly array $probes,
        private readonly int $defaultIterations = 10,
    ) {
        $this->runner = new Runner;
    }

    public static function fromConfig(string $configPath): self
    {
        if (! file_exists($configPath)) {
            throw new \InvalidArgumentException("Config file not found: {$configPath}");
        }

        $config = require $configPath;

        if (! is_array($config) || ! isset($config['probes'])) {
            throw new \InvalidArgumentException("Config must return an array with a 'probes' key");
        }

        $probes = array_filter(
            $config['probes'],
            fn (array $probe) => $probe['enabled'] ?? true,
        );

        // Resolve probe definitions
        foreach ($probes as $name => $probe) {
            if (is_array($probe['probe'])) {
                $class = array_key_first($probe['probe']);
                $args = $probe['probe'][$class];

                try {
                    $probes[$name]['probe'] = new $class(...$args);
                } catch (\Throwable $e) {
                    throw new \RuntimeException(
                        "Netwatch: failed to instantiate probe '{$name}' ({$class}): {$e->getMessage()}",
                        previous: $e,
                    );
                }
            }
        }

        return new self(
            probes: $probes,
            defaultIterations: $config['iterations'] ?? 10,
        );
    }

    /**
     * @param  string|array<string>|null  $probeName  Run only this probe or probes (null = all)
     * @param  int|null  $iterations  Override iterations for all probes
     * @return array<string, AggregateResult>
     */
    public function run(string|array|null $probeName = null, ?int $iterations = null): array
    {
        $probes = $this->probes;

        if (is_string($probeName)) {
            if (! isset($probes[$probeName])) {
                throw new \InvalidArgumentException("Probe not found: {$probeName}");
            }
            $probes = [$probeName => $probes[$probeName]];
        } elseif (is_array($probeName)) {
            $filtered = [];
            foreach ($probeName as $name) {
                if (! isset($probes[$name])) {
                    throw new \InvalidArgumentException("Probe not found: {$name}");
                }
                $filtered[$name] = $probes[$name];
            }
            $probes = $filtered;
        }

        $results = [];

        foreach ($probes as $name => $config) {
            $probe = $config['probe'];
            $count = $iterations ?? ($config['iterations'] ?? $this->defaultIterations);

            $results[$name] = $this->runner->run($probe, $count);
        }

        return $results;
    }

    /**
     * @return string[]
     */
    public function probeNames(): array
    {
        return array_keys($this->probes);
    }
}
