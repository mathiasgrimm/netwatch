<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Mathiasgrimm\Netwatch\Laravel\NetwatchServiceProvider;
use Orchestra\Testbench\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->app->register(NetwatchServiceProvider::class);
});

afterEach(function () {
    $configPath = config_path('netwatch.php');
    $providerPath = app_path('Providers/NetwatchServiceProvider.php');

    if (file_exists($configPath)) {
        unlink($configPath);
    }

    if (file_exists($providerPath)) {
        unlink($providerPath);
    }

    $providersDir = app_path('Providers');
    if (is_dir($providersDir) && count(scandir($providersDir)) === 2) {
        rmdir($providersDir);
    }
});

test('netwatch:install command is registered', function () {
    $commands = array_keys(Artisan::all());
    expect($commands)->toContain('netwatch:install');
});

test('netwatch:install returns success exit code', function () {
    $this->artisan('netwatch:install')->assertSuccessful();
});

test('netwatch:install publishes config file', function () {
    $this->artisan('netwatch:install');

    expect(file_exists(config_path('netwatch.php')))->toBeTrue();
});

test('netwatch:install publishes service provider', function () {
    $this->artisan('netwatch:install');

    expect(file_exists(app_path('Providers/NetwatchServiceProvider.php')))->toBeTrue();
});
