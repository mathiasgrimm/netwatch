<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;
use Mathiasgrimm\Netwatch\Laravel\NetwatchServiceProvider;
use Mathiasgrimm\Netwatch\Netwatch;
use Mathiasgrimm\Netwatch\Tests\Fixtures\SuccessProbe;
use Orchestra\Testbench\TestCase;

uses(TestCase::class);

function getPackageProviders($app): array
{
    return [NetwatchServiceProvider::class];
}

beforeEach(function () {
    // Re-register with providers since Pest's uses() doesn't call getPackageProviders
    $this->app->register(NetwatchServiceProvider::class);
});

test('service provider is registered', function () {
    expect($this->app->getProviders(NetwatchServiceProvider::class))->not->toBeEmpty();
});

test('default config is merged', function () {
    expect(config('netwatch.iterations'))->toBe(10)
        ->and(config('netwatch.probes'))->toBeArray()
        ->and(config('netwatch.health_route.enabled'))->toBeFalse()
        ->and(config('netwatch.health_route.path'))->toBe('netwatch');

    // All probes are disabled by default
    foreach (config('netwatch.probes') as $probe) {
        expect($probe['enabled'])->toBeFalse();
    }
});

test('netwatch singleton is bound', function () {
    expect($this->app->bound(Netwatch::class))->toBeTrue();

    $instance = $this->app->make(Netwatch::class);
    expect($instance)->toBeInstanceOf(Netwatch::class);
});

test('singleton uses config values', function () {
    config(['netwatch.iterations' => 25]);
    // Clear cached singleton so it picks up new config
    $this->app->forgetInstance(Netwatch::class);

    $netwatch = $this->app->make(Netwatch::class);
    expect($netwatch->probeNames())->toBe([]);
});

test('singleton resolves with probes from config', function () {
    config([
        'netwatch.probes' => [
            'test' => [
                'enabled' => true,
                'probe' => new SuccessProbe,
            ],
        ],
    ]);
    $this->app->forgetInstance(Netwatch::class);

    $netwatch = $this->app->make(Netwatch::class);

    expect($netwatch->probeNames())->toBe(['test']);

    $results = $netwatch->run();
    expect($results)->toHaveKey('test')
        ->and($results['test']->failures)->toBe(0);
});

test('singleton resolves array-based probe config', function () {
    config([
        'netwatch.probes' => [
            'test' => [
                'enabled' => true,
                'probe' => [
                    SuccessProbe::class => [],
                ],
            ],
        ],
    ]);
    $this->app->forgetInstance(Netwatch::class);

    $netwatch = $this->app->make(Netwatch::class);
    expect($netwatch->probeNames())->toBe(['test']);

    $results = $netwatch->run();
    expect($results['test']->failures)->toBe(0);
});

test('singleton resolves string-based probe via container', function () {
    $this->app->bind('test-probe', fn () => new SuccessProbe);

    config([
        'netwatch.probes' => [
            'test' => [
                'enabled' => true,
                'probe' => 'test-probe',
            ],
        ],
    ]);
    $this->app->forgetInstance(Netwatch::class);

    $netwatch = $this->app->make(Netwatch::class);
    expect($netwatch->probeNames())->toBe(['test']);

    $results = $netwatch->run();
    expect($results['test']->failures)->toBe(0);
});

test('artisan netwatch:run command is registered', function () {
    $commands = array_keys(Artisan::all());
    expect($commands)->toContain('netwatch:run');
});

test('artisan netwatch:run works with json output', function () {
    config([
        'netwatch.iterations' => 2,
        'netwatch.probes' => [
            'test-probe' => [
                'enabled' => true,
                'probe' => new SuccessProbe,
            ],
        ],
    ]);
    $this->app->forgetInstance(Netwatch::class);

    $this->artisan('netwatch:run', ['--json' => true])
        ->assertSuccessful();
});

test('health route is not registered by default', function () {
    $routes = collect($this->app['router']->getRoutes()->getRoutes());
    $healthRoute = $routes->first(fn ($route) => $route->uri() === 'netwatch/health');

    expect($healthRoute)->toBeNull();
});

test('health route is registered when enabled', function () {
    config([
        'netwatch.health_route.enabled' => true,
        'netwatch.health_route.middleware' => [],
    ]);

    // Re-register provider so boot() picks up new config
    $this->app->register(NetwatchServiceProvider::class, true);

    $routes = collect($this->app['router']->getRoutes()->getRoutes());
    $healthRoute = $routes->first(fn ($route) => $route->uri() === 'netwatch/health');

    expect($healthRoute)->not->toBeNull();
});

test('health route uses custom path prefix', function () {
    config([
        'netwatch.health_route.enabled' => true,
        'netwatch.health_route.path' => 'custom',
        'netwatch.health_route.middleware' => [],
    ]);

    $this->app->register(NetwatchServiceProvider::class, true);

    $routes = collect($this->app['router']->getRoutes()->getRoutes());
    $healthRoute = $routes->first(fn ($route) => $route->uri() === 'custom/health');

    expect($healthRoute)->not->toBeNull();
});

test('health route returns json with probe results', function () {
    config([
        'netwatch.health_route.enabled' => true,
        'netwatch.health_route.middleware' => [],
        'netwatch.iterations' => 2,
        'netwatch.probes' => [
            'my-probe' => [
                'enabled' => true,
                'probe' => new SuccessProbe,
            ],
        ],
    ]);
    $this->app->forgetInstance(Netwatch::class);
    $this->app->register(NetwatchServiceProvider::class, true);

    $response = $this->get('/netwatch/health', ['Accept' => 'application/json']);

    $response->assertOk()
        ->assertJsonStructure([
            'my-probe' => [
                'name',
                'iterations',
                'stats' => [
                    'connect_ms' => ['min', 'max', 'avg', 'p50', 'p95', 'p99'],
                    'request_ms' => ['min', 'max', 'avg', 'p50', 'p95', 'p99'],
                    'total_ms' => ['min', 'max', 'avg', 'p50', 'p95', 'p99'],
                ],
                'failures',
                'results',
            ],
        ]);
});

test('config can be published', function () {
    $publishable = ServiceProvider::$publishes[NetwatchServiceProvider::class] ?? [];

    expect($publishable)->not->toBeEmpty();

    $source = array_key_first($publishable);
    expect($source)->toContain('config/netwatch.php')
        ->and(file_exists($source))->toBeTrue();
});
