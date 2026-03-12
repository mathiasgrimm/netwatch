<?php

declare(strict_types=1);

use Mathiasgrimm\Netwatch\Netwatch;
use Mathiasgrimm\Netwatch\Result\AggregateResult;

function writeTestConfig(string $dir, array $config): string
{
    $path = $dir . '/netwatch.php';

    $probesPhp = "[\n";
    foreach ($config['probes'] as $name => $probe) {
        $probesPhp .= "        '$name' => [\n";
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
    $this->configDir = sys_get_temp_dir() . '/netwatch_test_' . uniqid();
    mkdir($this->configDir);
});

afterEach(function () {
    array_map('unlink', glob($this->configDir . '/*'));
    rmdir($this->configDir);
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
    $path = $this->configDir . '/bad.php';
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
