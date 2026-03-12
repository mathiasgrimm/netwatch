<?php

declare(strict_types=1);

use Mathiasgrimm\Netwatch\Console\InitCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

function createInitCommandTester(): CommandTester
{
    $app = new Application;
    $app->add(new InitCommand);

    return new CommandTester($app->find('netwatch:init'));
}

beforeEach(function () {
    $this->originalCwd = getcwd();
    $this->tempDir = sys_get_temp_dir().'/netwatch_init_test_'.uniqid();
    mkdir($this->tempDir);
    chdir($this->tempDir);
});

afterEach(function () {
    chdir($this->originalCwd);
    array_map('unlink', glob($this->tempDir.'/*'));

    if (is_dir($this->tempDir.'/bootstrap')) {
        array_map('unlink', glob($this->tempDir.'/bootstrap/*'));
        rmdir($this->tempDir.'/bootstrap');
    }

    rmdir($this->tempDir);
});

test('generates standalone config', function () {
    $tester = createInitCommandTester();
    $tester->execute([]);

    $content = file_get_contents($this->tempDir.'/netwatch.php');

    expect($tester->getStatusCode())->toBe(0)
        ->and($tester->getDisplay())->toContain('standalone')
        ->and($content)->toContain('TcpPingProbe')
        ->and($content)->toContain('PhpRedisProbe')
        ->and($content)->toContain("'iterations' => 10")
        ->and($content)->not->toContain('bootstrap/app.php');
});

test('generates laravel config with flag', function () {
    $tester = createInitCommandTester();
    $tester->execute(['--laravel' => true]);

    $content = file_get_contents($this->tempDir.'/netwatch.php');

    expect($tester->getStatusCode())->toBe(0)
        ->and($tester->getDisplay())->toContain('Laravel')
        ->and($content)->toContain('bootstrap/app.php')
        ->and($content)->toContain("config('database.connections.'")
        ->and($content)->toContain("config('database.redis.default')")
        ->and($content)->toContain("config('filesystems.disks.s3')");
});

test('auto-detects laravel', function () {
    mkdir($this->tempDir.'/bootstrap');
    file_put_contents($this->tempDir.'/bootstrap/app.php', '<?php');
    file_put_contents($this->tempDir.'/artisan', '<?php');

    $tester = createInitCommandTester();
    $tester->execute([]);

    $content = file_get_contents($this->tempDir.'/netwatch.php');

    expect($tester->getDisplay())->toContain('Laravel')
        ->and($content)->toContain('bootstrap/app.php');
});

test('refuses to overwrite existing file', function () {
    file_put_contents($this->tempDir.'/netwatch.php', '<?php // existing');

    $tester = createInitCommandTester();
    $tester->execute([]);

    expect($tester->getStatusCode())->toBe(1)
        ->and($tester->getDisplay())->toContain('already exists')
        ->and(file_get_contents($this->tempDir.'/netwatch.php'))->toBe('<?php // existing');
});

test('force overwrites existing file', function () {
    file_put_contents($this->tempDir.'/netwatch.php', '<?php // existing');

    $tester = createInitCommandTester();
    $tester->execute(['--force' => true]);

    $content = file_get_contents($this->tempDir.'/netwatch.php');

    expect($tester->getStatusCode())->toBe(0)
        ->and($content)->toContain('TcpPingProbe');
});

test('standalone config is valid php', function () {
    createInitCommandTester()->execute([]);

    $output = shell_exec('php -l '.escapeshellarg($this->tempDir.'/netwatch.php').' 2>&1');

    expect($output)->toContain('No syntax errors');
});

test('laravel config is valid php', function () {
    createInitCommandTester()->execute(['--laravel' => true]);

    $output = shell_exec('php -l '.escapeshellarg($this->tempDir.'/netwatch.php').' 2>&1');

    expect($output)->toContain('No syntax errors');
});
