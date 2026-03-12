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
    rmdir($this->tempDir);
});

test('generates config', function () {
    $tester = createInitCommandTester();
    $tester->execute([]);

    $content = file_get_contents($this->tempDir.'/netwatch.php');

    expect($tester->getStatusCode())->toBe(0)
        ->and($tester->getDisplay())->toContain('Created netwatch.php')
        ->and($content)->toContain('TcpPingProbe')
        ->and($content)->toContain('PhpRedisProbe')
        ->and($content)->toContain("'iterations' => 10");
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

test('config is valid php', function () {
    createInitCommandTester()->execute([]);

    $output = shell_exec('php -l '.escapeshellarg($this->tempDir.'/netwatch.php').' 2>&1');

    expect($output)->toContain('No syntax errors');
});
