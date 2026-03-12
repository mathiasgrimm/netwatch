<?php

declare(strict_types=1);

use Mathiasgrimm\Netwatch\Console\NetwatchCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

function createRunCommandTester(): CommandTester
{
    $app = new Application();
    $app->add(new NetwatchCommand());
    return new CommandTester($app->find('netwatch:run'));
}

function writeRunConfig(string $dir, ?string $content = null): string
{
    $path = $dir . '/netwatch.php';
    file_put_contents($path, $content ?? <<<'PHP'
<?php
return [
    'iterations' => 3,
    'probes' => [
        'test-probe' => [
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
            'probe' => new \Mathiasgrimm\Netwatch\Tests\Fixtures\SuccessProbe(),
        ],
        'probe-b' => [
            'probe' => new \Mathiasgrimm\Netwatch\Tests\Fixtures\SuccessProbe(),
        ],
    ],
];
PHP);
}

beforeEach(function () {
    $this->configDir = sys_get_temp_dir() . '/netwatch_cmd_test_' . uniqid();
    mkdir($this->configDir);
});

afterEach(function () {
    array_map('unlink', glob($this->configDir . '/*'));
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
