<?php

declare(strict_types=1);

use Mathiasgrimm\Netwatch\Console\NetwatchCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

function createRunCommandTester(): CommandTester
{
    $app = new Application;
    $app->add(new NetwatchCommand);

    return new CommandTester($app->find('netwatch:run'));
}

function writeRunConfig(string $dir, ?string $content = null): string
{
    $path = $dir.'/netwatch.php';
    file_put_contents($path, $content ?? <<<'PHP'
<?php
return [
    'iterations' => 3,
    'probes' => [
        'test-probe' => [
            'enabled' => true,
            'probe' => new \Mathiasgrimm\Netwatch\Tests\Fixtures\SuccessProbe(),
        ],
    ],
];
PHP);

    return $path;
}

function writeMultiConfig(string $dir): string
{
    return writeRunConfig($dir, <<<'PHP'
<?php
return [
    'iterations' => 2,
    'probes' => [
        'probe-a' => [
            'enabled' => true,
            'probe' => new \Mathiasgrimm\Netwatch\Tests\Fixtures\SuccessProbe(),
        ],
        'probe-b' => [
            'enabled' => true,
            'probe' => new \Mathiasgrimm\Netwatch\Tests\Fixtures\SuccessProbe(),
        ],
    ],
];
PHP);
}

beforeEach(function () {
    $this->configDir = sys_get_temp_dir().'/netwatch_cmd_test_'.uniqid();
    mkdir($this->configDir);
});

afterEach(function () {
    array_map('unlink', glob($this->configDir.'/*'));
    rmdir($this->configDir);
});

test('table output contains expected columns', function () {
    $tester = createRunCommandTester();
    $tester->execute([
        '--config' => writeRunConfig($this->configDir),
        '--sequential' => true,
    ]);

    $output = $tester->getDisplay();

    expect($tester->getStatusCode())->toBe(0)
        ->and($output)->toContain('Probe')
        ->and($output)->toContain('Iterations')
        ->and($output)->toContain('connect')
        ->and($output)->toContain('request')
        ->and($output)->toContain('total')
        ->and($output)->toContain('test-probe');
});

test('json output is valid json', function () {
    $tester = createRunCommandTester();
    $tester->execute([
        '--config' => writeRunConfig($this->configDir),
        '--json' => true,
        '--sequential' => true,
    ]);

    $data = json_decode($tester->getDisplay(), true);

    expect($tester->getStatusCode())->toBe(0)
        ->and($data)->toBeArray()
        ->and($data)->toHaveKey('test-probe')
        ->and($data['test-probe'])->toHaveKey('stats')
        ->and($data['test-probe']['stats'])->toHaveKey('connect_ms');
});

test('iterations override', function () {
    $tester = createRunCommandTester();
    $tester->execute([
        '--config' => writeRunConfig($this->configDir),
        '--iterations' => '5',
        '--json' => true,
        '--sequential' => true,
    ]);

    $data = json_decode($tester->getDisplay(), true);

    expect($data['test-probe']['iterations'])->toBe(5);
});

test('probe filter', function () {
    $tester = createRunCommandTester();
    $tester->execute([
        '--config' => writeMultiConfig($this->configDir),
        '--probe' => 'probe-a',
        '--json' => true,
    ]);

    $data = json_decode($tester->getDisplay(), true);

    expect($data)->toHaveCount(1)->toHaveKey('probe-a');
});

test('missing config shows error', function () {
    $tester = createRunCommandTester();
    $tester->execute([
        '--config' => '/nonexistent/config.php',
        '--sequential' => true,
    ]);

    expect($tester->getStatusCode())->toBe(1)
        ->and($tester->getDisplay())->toContain('Config file not found');
});

test('unknown probe shows error', function () {
    $tester = createRunCommandTester();
    $tester->execute([
        '--config' => writeRunConfig($this->configDir),
        '--probe' => 'nonexistent',
        '--sequential' => true,
    ]);

    expect($tester->getStatusCode())->toBe(1)
        ->and($tester->getDisplay())->toContain('Probe not found');
});

test('multiple probes in table', function () {
    $tester = createRunCommandTester();
    $tester->execute([
        '--config' => writeMultiConfig($this->configDir),
        '--sequential' => true,
    ]);

    $output = $tester->getDisplay();

    expect($output)->toContain('probe-a')->toContain('probe-b');
});

test('zero failures for success probe', function () {
    $tester = createRunCommandTester();
    $tester->execute([
        '--config' => writeRunConfig($this->configDir),
        '--json' => true,
        '--sequential' => true,
    ]);

    $data = json_decode($tester->getDisplay(), true);

    expect($data['test-probe']['failures'])->toBe(0);
});

function writeThresholdConfig(string $dir, ?float $warn, ?float $crit, string $probe = 'SuccessProbe'): string
{
    $warnValue = $warn === null ? 'null' : (string) $warn;
    $critValue = $crit === null ? 'null' : (string) $crit;

    return writeRunConfig($dir, <<<PHP
<?php
return [
    'iterations' => 2,
    'probes' => [
        'test-probe' => [
            'enabled' => true,
            'thresholds' => ['warn' => {$warnValue}, 'crit' => {$critValue}],
            'probe' => new \\Mathiasgrimm\\Netwatch\\Tests\\Fixtures\\{$probe}(),
        ],
    ],
];
PHP);
}

test('json output includes status and thresholds', function () {
    $tester = createRunCommandTester();
    // SuccessProbe totals 3.0 ms; warn 2.5 puts it over warn only
    $tester->execute([
        '--config' => writeThresholdConfig($this->configDir, 2.5, 10.0),
        '--json' => true,
        '--sequential' => true,
    ]);

    $data = json_decode($tester->getDisplay(), true);

    expect($data['test-probe']['status'])->toBe('warn')
        ->and($data['test-probe']['thresholds'])->toBe(['warn' => 2.5, 'crit' => 10])
        ->and($data['test-probe']['over_warn'])->toBe(2)
        ->and($data['test-probe']['over_crit'])->toBe(0)
        ->and($data['test-probe']['results'][0]['status'])->toBe('warn');
});

test('table shows status column', function () {
    $tester = createRunCommandTester();
    $tester->execute([
        '--config' => writeThresholdConfig($this->configDir, 1.0, 2.0),
        '--sequential' => true,
    ]);

    expect($tester->getDisplay())->toContain('Status')->toContain('crit');
});

test('fail-on-crit exits non-zero on crit breach', function () {
    $tester = createRunCommandTester();
    $tester->execute([
        '--config' => writeThresholdConfig($this->configDir, 1.0, 2.0),
        '--sequential' => true,
        '--fail-on-crit' => true,
    ]);

    expect($tester->getStatusCode())->toBe(1);
});

test('fail-on-crit exits non-zero on failing probe', function () {
    $tester = createRunCommandTester();
    $tester->execute([
        '--config' => writeThresholdConfig($this->configDir, null, null, 'FailingProbe'),
        '--sequential' => true,
        '--fail-on-crit' => true,
    ]);

    expect($tester->getStatusCode())->toBe(1);
});

test('fail-on-crit exits zero when within thresholds', function () {
    $tester = createRunCommandTester();
    $tester->execute([
        '--config' => writeThresholdConfig($this->configDir, 100.0, 200.0),
        '--sequential' => true,
        '--fail-on-crit' => true,
    ]);

    expect($tester->getStatusCode())->toBe(0);
});

test('crit breach without fail-on-crit still exits zero', function () {
    $tester = createRunCommandTester();
    $tester->execute([
        '--config' => writeThresholdConfig($this->configDir, 1.0, 2.0),
        '--sequential' => true,
    ]);

    expect($tester->getStatusCode())->toBe(0);
});
