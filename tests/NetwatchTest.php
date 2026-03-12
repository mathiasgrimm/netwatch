<?php

declare(strict_types=1);

use Mathiasgrimm\Netwatch\Netwatch;
use Mathiasgrimm\Netwatch\Result\AggregateResult;
use Mathiasgrimm\Netwatch\Tests\Fixtures\SuccessProbe;

function writeTestConfig(string $dir, array $config): string
{
    $path = $dir.'/netwatch.php';

    $probesPhp = "[\n";
    foreach ($config['probes'] as $name => $probe) {
        $probesPhp .= "        '$name' => [\n";
        $probesPhp .= "            'enabled' => true,\n";
        $probesPhp .= "            'probe' => {$probe['probe']},\n";
        if (isset($probe['iterations'])) {
            $probesPhp .= "            'iterations' => {$probe['iterations']},\n";
        }
        $probesPhp .= "        ],\n";
    }
    $probesPhp .= '    ]';

    $iterations = $config['iterations'] ?? 10;

    file_put_contents($path, <<<PHP
<?php
return [
    'iterations' => {$iterations},
    'probes' => {$probesPhp},
];
PHP);

    return $path;
}

beforeEach(function () {
    $this->configDir = sys_get_temp_dir().'/netwatch_test_'.uniqid();
    mkdir($this->configDir);
});

afterEach(function () {
    array_map('unlink', glob($this->configDir.'/*'));
    rmdir($this->configDir);
    Netwatch::resolveProbesUsing(null);
});

test('fromConfig loads file', function () {
    $path = writeTestConfig($this->configDir, [
        'iterations' => 3,
        'probes' => [
            'test' => ['probe' => 'new \Mathiasgrimm\Netwatch\Tests\Fixtures\SuccessProbe()'],
        ],
    ]);

    $netwatch = Netwatch::fromConfig($path);
    expect($netwatch->probeNames())->toBe(['test']);
});

test('fromConfig throws on missing file', function () {
    Netwatch::fromConfig('/nonexistent/netwatch.php');
})->throws(InvalidArgumentException::class, 'Config file not found');

test('fromConfig throws on invalid config', function () {
    $path = $this->configDir.'/bad.php';
    file_put_contents($path, '<?php return "not an array";');

    Netwatch::fromConfig($path);
})->throws(InvalidArgumentException::class, "'probes' key");

test('run all probes', function () {
    $path = writeTestConfig($this->configDir, [
        'iterations' => 2,
        'probes' => [
            'probe-a' => ['probe' => 'new \Mathiasgrimm\Netwatch\Tests\Fixtures\SuccessProbe()'],
            'probe-b' => ['probe' => 'new \Mathiasgrimm\Netwatch\Tests\Fixtures\SuccessProbe()'],
        ],
    ]);

    $results = Netwatch::fromConfig($path)->run();

    expect($results)->toHaveCount(2)
        ->toHaveKey('probe-a')
        ->toHaveKey('probe-b')
        ->and($results['probe-a'])->toBeInstanceOf(AggregateResult::class)
        ->and($results['probe-a']->iterations)->toBe(2);
});

test('run single probe', function () {
    $path = writeTestConfig($this->configDir, [
        'iterations' => 2,
        'probes' => [
            'probe-a' => ['probe' => 'new \Mathiasgrimm\Netwatch\Tests\Fixtures\SuccessProbe()'],
            'probe-b' => ['probe' => 'new \Mathiasgrimm\Netwatch\Tests\Fixtures\SuccessProbe()'],
        ],
    ]);

    $results = Netwatch::fromConfig($path)->run('probe-a');

    expect($results)->toHaveCount(1)->toHaveKey('probe-a');
});

test('run unknown probe throws', function () {
    $path = writeTestConfig($this->configDir, [
        'iterations' => 1,
        'probes' => [
            'probe-a' => ['probe' => 'new \Mathiasgrimm\Netwatch\Tests\Fixtures\SuccessProbe()'],
        ],
    ]);

    Netwatch::fromConfig($path)->run('nope');
})->throws(InvalidArgumentException::class, 'Probe not found: nope');

test('iteration override from run()', function () {
    $path = writeTestConfig($this->configDir, [
        'iterations' => 2,
        'probes' => [
            'test' => ['probe' => 'new \Mathiasgrimm\Netwatch\Tests\Fixtures\SuccessProbe()'],
        ],
    ]);

    $results = Netwatch::fromConfig($path)->run(null, 5);

    expect($results['test']->iterations)->toBe(5);
});

test('per-probe iteration override', function () {
    $path = writeTestConfig($this->configDir, [
        'iterations' => 2,
        'probes' => [
            'test' => [
                'probe' => 'new \Mathiasgrimm\Netwatch\Tests\Fixtures\SuccessProbe()',
                'iterations' => 7,
            ],
        ],
    ]);

    $results = Netwatch::fromConfig($path)->run();

    expect($results['test']->iterations)->toBe(7);
});

test('default iterations is 10', function () {
    $path = writeTestConfig($this->configDir, [
        'probes' => [
            'test' => ['probe' => 'new \Mathiasgrimm\Netwatch\Tests\Fixtures\SuccessProbe()'],
        ],
    ]);

    $results = Netwatch::fromConfig($path)->run();

    expect($results['test']->iterations)->toBe(10);
});

test('fromArray resolves array-based probe config', function () {
    $netwatch = Netwatch::fromArray([
        'iterations' => 2,
        'probes' => [
            'test' => [
                'enabled' => true,
                'probe' => [
                    SuccessProbe::class => [],
                ],
            ],
        ],
    ]);

    expect($netwatch->probeNames())->toBe(['test']);

    $results = $netwatch->run();
    expect($results['test']->failures)->toBe(0)
        ->and($results['test']->iterations)->toBe(2);
});

test('fromArray filters disabled probes', function () {
    $netwatch = Netwatch::fromArray([
        'probes' => [
            'enabled' => [
                'enabled' => true,
                'probe' => new SuccessProbe,
            ],
            'disabled' => [
                'enabled' => false,
                'probe' => new SuccessProbe,
            ],
        ],
    ]);

    expect($netwatch->probeNames())->toBe(['enabled']);
});

test('fromArray with direct instance probe', function () {
    $netwatch = Netwatch::fromArray([
        'probes' => [
            'test' => [
                'enabled' => true,
                'probe' => new SuccessProbe,
            ],
        ],
    ]);

    expect($netwatch->probeNames())->toBe(['test']);
});

test('resolveProbesUsing closure is called for each probe', function () {
    Netwatch::resolveProbesUsing(function (string $name, mixed $probe) {
        return new SuccessProbe;
    });

    $netwatch = Netwatch::fromArray([
        'probes' => [
            'custom' => [
                'enabled' => true,
                'probe' => 'some-string-reference',
            ],
        ],
    ]);

    expect($netwatch->probeNames())->toBe(['custom']);

    $results = $netwatch->run();
    expect($results['custom']->failures)->toBe(0);
});

test('resolveProbesUsing can delegate to resolveProbe', function () {
    Netwatch::resolveProbesUsing(function (string $name, mixed $probe) {
        return Netwatch::resolveProbe($name, $probe);
    });

    $netwatch = Netwatch::fromArray([
        'probes' => [
            'test' => [
                'enabled' => true,
                'probe' => [
                    SuccessProbe::class => [],
                ],
            ],
        ],
    ]);

    expect($netwatch->probeNames())->toBe(['test']);
});

test('ensureProbeContract rejects non-ProbeInterface', function () {
    new Netwatch(probes: [
        'bad' => ['probe' => 'not-a-probe'],
    ]);
})->throws(InvalidArgumentException::class, "probe 'bad' must implement ProbeInterface");

test('resolveProbe throws on invalid class', function () {
    Netwatch::resolveProbe('broken', ['NonExistentClass12345' => []]);
})->throws(RuntimeException::class, "failed to instantiate probe 'broken'");

test('probeNames returns all names', function () {
    $path = writeTestConfig($this->configDir, [
        'iterations' => 1,
        'probes' => [
            'alpha' => ['probe' => 'new \Mathiasgrimm\Netwatch\Tests\Fixtures\SuccessProbe()'],
            'beta' => ['probe' => 'new \Mathiasgrimm\Netwatch\Tests\Fixtures\SuccessProbe()'],
            'gamma' => ['probe' => 'new \Mathiasgrimm\Netwatch\Tests\Fixtures\SuccessProbe()'],
        ],
    ]);

    expect(Netwatch::fromConfig($path)->probeNames())->toBe(['alpha', 'beta', 'gamma']);
});
