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
        $this->components->info('Installing Netwatch resources.');

        collect([
            'Service Provider' => fn () => $this->callSilent('vendor:publish', ['--tag' => 'netwatch-provider', '--force' => $this->option('force')]) == 0,
            'Configuration' => fn () => $this->callSilent('vendor:publish', ['--tag' => 'netwatch-config', '--force' => $this->option('force')]) == 0,
        ])->each(fn ($task, $description) => $this->components->task($description, $task));

        $this->registerNetwatchServiceProvider();

        $this->components->info('Netwatch scaffolding installed successfully.');

        return self::SUCCESS;
    }

    protected function registerNetwatchServiceProvider(): void
    {
        $namespace = Str::replaceLast('\\', '', $this->laravel->getNamespace());

        if (file_exists($this->laravel->bootstrapPath('providers.php'))) {
            ServiceProvider::addProviderToBootstrapFile("{$namespace}\\Providers\\NetwatchServiceProvider");
        } else {
            $appConfig = file_get_contents(config_path('app.php'));

            if (Str::contains($appConfig, $namespace.'\\Providers\\NetwatchServiceProvider::class')) {
                return;
            }

            file_put_contents(config_path('app.php'), str_replace(
                "{$namespace}\\Providers\EventServiceProvider::class,".PHP_EOL,
                "{$namespace}\\Providers\EventServiceProvider::class,".PHP_EOL."        {$namespace}\Providers\NetwatchServiceProvider::class,".PHP_EOL,
                $appConfig
            ));
        }

        file_put_contents(app_path('Providers/NetwatchServiceProvider.php'), str_replace(
            "namespace App\Providers;",
            "namespace {$namespace}\Providers;",
            file_get_contents(app_path('Providers/NetwatchServiceProvider.php'))
        ));
    }
}
