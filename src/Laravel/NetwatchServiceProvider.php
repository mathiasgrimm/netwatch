<?php

declare(strict_types=1);

namespace Mathiasgrimm\Netwatch\Laravel;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Mathiasgrimm\Netwatch\Console\InitCommand;
use Mathiasgrimm\Netwatch\Laravel\Console\NetwatchCommand;
use Mathiasgrimm\Netwatch\Laravel\Http\Middleware\Authorize;
use Mathiasgrimm\Netwatch\Netwatch;
use Throwable;

class NetwatchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/config/netwatch.php', 'netwatch');

        $this->app->singleton(Netwatch::class, function () {
            $config = config('netwatch');

            $probes = array_filter(
                $config['probes'] ?? [],
                fn (array $probe) => $probe['enabled'] ?? true,
            );

            $probes = $this->resolveProbes($probes);

            return new Netwatch(
                probes: $probes,
                defaultIterations: $config['iterations'] ?? 10,
            );
        });
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', 'netwatch');

        $this->registerRoutes();
        $this->registerPublishing();

        if ($this->app->runningInConsole()) {
            $this->commands([
                NetwatchCommand::class,
                InitCommand::class,
            ]);
        }
    }

    private function resolveProbes(array $probes): array
    {
        $probes = Netwatch::resolveArrayProbes($probes);

        foreach ($probes as $name => $probe) {
            if (is_string($probe['probe'])) {
                try {
                    $probes[$name]['probe'] = $this->app->make($probe['probe']);
                } catch (Throwable $e) {
                    throw new \RuntimeException(
                        "Netwatch: failed to resolve probe '{$name}' ({$probe['probe']}) from container: {$e->getMessage()}",
                        previous: $e,
                    );
                }
            }
        }

        return $probes;
    }

    private function registerRoutes(): void
    {
        if (! config('netwatch.health_route.enabled', false)) {
            return;
        }

        Route::group([
            'domain' => config('netwatch.health_route.domain'),
            'prefix' => config('netwatch.health_route.path', 'netwatch'),
            'middleware' => config('netwatch.health_route.middleware', ['web', Authorize::class]),
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        });
    }

    private function registerPublishing(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/config/netwatch.php' => config_path('netwatch.php'),
        ], 'netwatch-config');

        $this->publishes([
            __DIR__.'/resources/views' => resource_path('views/vendor/netwatch'),
        ], 'netwatch-views');

        $this->publishes([
            __DIR__.'/../../stubs/NetwatchServiceProvider.stub' => app_path('Providers/NetwatchServiceProvider.php'),
        ], 'netwatch-provider');
    }
}
