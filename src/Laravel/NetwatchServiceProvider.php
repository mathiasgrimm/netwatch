<?php

declare(strict_types=1);

namespace Mathiasgrimm\Netwatch\Laravel;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Mathiasgrimm\Netwatch\Laravel\Console\InstallCommand;
use Mathiasgrimm\Netwatch\Laravel\Console\NetwatchCommand;
use Mathiasgrimm\Netwatch\Laravel\Http\Middleware\Authorize;
use Mathiasgrimm\Netwatch\Netwatch;
use RuntimeException;
use Throwable;

class NetwatchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/config/netwatch.php', 'netwatch');

        Netwatch::resolveProbesUsing(function (string $name, mixed $probe) {
            if (is_string($probe)) {
                try {
                    return $this->app->make($probe);
                } catch (Throwable $e) {
                    throw new RuntimeException(
                        "Netwatch: failed to resolve probe '{$name}' ({$probe}) from container: {$e->getMessage()}",
                        previous: $e,
                    );
                }
            }

            return Netwatch::resolveProbe($name, $probe);
        });

        $this->app->singleton(Netwatch::class, function () {
            $config = config('netwatch');
            $config['probes'] = self::withDefaultThresholds($config['probes'] ?? []);

            return Netwatch::fromArray($config);
        });
    }

    /**
     * A probe config without a 'thresholds' key (e.g. a config file published
     * before thresholds existed) falls back to the package defaults for that
     * probe. A 'thresholds' key that is present but null is an explicit
     * opt-out and disables the budget.
     */
    private static function withDefaultThresholds(array $probes): array
    {
        $defaults = null;

        foreach ($probes as $name => $probe) {
            if (! array_key_exists('thresholds', $probe)) {
                $defaults ??= (require __DIR__.'/config/netwatch.php')['probes'] ?? [];
                $probes[$name]['thresholds'] = $defaults[$name]['thresholds'] ?? null;
            }
        }

        return $probes;
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', 'netwatch');

        $this->registerRoutes();
        $this->registerPublishing();

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                NetwatchCommand::class,
            ]);
        }
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
