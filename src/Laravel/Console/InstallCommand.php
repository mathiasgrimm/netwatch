<?php

declare(strict_types=1);

namespace Mathiasgrimm\Netwatch\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class InstallCommand extends Command
{
    protected $signature = 'netwatch:install {--force : Overwrite any existing files}';

    protected $description = 'Install all of the Netwatch resources';

    public function handle(): int
    {
        $this->components->info('Publishing Netwatch config...');
        $this->callSilent('vendor:publish', [
            '--tag' => 'netwatch-config',
            '--force' => $this->option('force'),
        ]);

        $this->components->info('Publishing Netwatch service provider...');
        $this->callSilent('vendor:publish', [
            '--tag' => 'netwatch-provider',
            '--force' => $this->option('force'),
        ]);

        $namespace = Str::replaceLast('\\', '', $this->laravel->getNamespace());

        if ($namespace !== 'App') {
            $providerPath = app_path('Providers/NetwatchServiceProvider.php');

            if (file_exists($providerPath)) {
                file_put_contents(
                    $providerPath,
                    str_replace('App\Providers', $namespace.'\Providers', file_get_contents($providerPath)),
                );
            }
        }

        if (method_exists(ServiceProvider::class, 'addProviderToBootstrapFile')) {
            ServiceProvider::addProviderToBootstrapFile($namespace.'\Providers\NetwatchServiceProvider');
            $this->components->info('Service provider registered in bootstrap/providers.php.');
        }

        $this->components->info('Netwatch installed successfully.');

        return self::SUCCESS;
    }
}
