<?php

declare(strict_types=1);

use Mathiasgrimm\Netwatch\Laravel\Http\Middleware\Authorize;
use Mathiasgrimm\Netwatch\Laravel\NetwatchServiceProvider;
use Mathiasgrimm\Netwatch\Netwatch;
use Mathiasgrimm\Netwatch\Tests\Fixtures\SuccessProbe;
use Orchestra\Testbench\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->app->register(NetwatchServiceProvider::class);

    config([
        'netwatch.health_route.enabled' => true,
        'netwatch.health_route.middleware' => [Authorize::class],
        'netwatch.iterations' => 2,
        'netwatch.probes' => [
            'test-success' => ['enabled' => true, 'probe' => new SuccessProbe],
        ],
    ]);

    Netwatch::auth(function () {
        return false;
    });

    $this->app->forgetInstance(Netwatch::class);
    $this->app->register(NetwatchServiceProvider::class, true);
});

afterEach(function () {
    Netwatch::auth(fn () => true);
});

test('token configured and matching query param returns 200', function () {
    config(['netwatch.health_route.token' => 'secret-token']);

    $this->get('/netwatch/health?token=secret-token', ['Accept' => 'application/json'])
        ->assertOk();
});

test('token configured and wrong query param returns 403', function () {
    config(['netwatch.health_route.token' => 'secret-token']);

    $this->get('/netwatch/health?token=wrong-token', ['Accept' => 'application/json'])
        ->assertForbidden();
});

test('token configured and no query param falls through to regular auth', function () {
    config(['netwatch.health_route.token' => 'secret-token']);

    $this->get('/netwatch/health', ['Accept' => 'application/json'])
        ->assertForbidden();
});

test('token not configured uses regular auth only', function () {
    config(['netwatch.health_route.token' => null]);

    $this->get('/netwatch/health', ['Accept' => 'application/json'])
        ->assertForbidden();
});

test('no auth callback and no token denies access', function () {
    config(['netwatch.health_route.token' => null]);

    // Clear any registered auth callback so the gate has nothing that passes.
    $property = new ReflectionProperty(Netwatch::class, 'authUsing');
    $property->setValue(null, null);

    $this->get('/netwatch/health', ['Accept' => 'application/json'])
        ->assertForbidden();
});

test('auth callback returning true allows access', function () {
    config(['netwatch.health_route.token' => null]);

    Netwatch::auth(fn () => true);

    $this->get('/netwatch/health', ['Accept' => 'application/json'])
        ->assertOk();
});

test('default gate allows the local environment', function () {
    config(['netwatch.health_route.token' => null]);

    // No app-registered gate: let the provider install its default.
    (new ReflectionProperty(Netwatch::class, 'authUsing'))->setValue(null, null);
    $this->app['env'] = 'local';
    $this->app->register(NetwatchServiceProvider::class, true);

    $this->get('/netwatch/health', ['Accept' => 'application/json'])
        ->assertOk();
});

test('default gate denies non-local environments', function () {
    config(['netwatch.health_route.token' => null]);

    (new ReflectionProperty(Netwatch::class, 'authUsing'))->setValue(null, null);
    $this->app['env'] = 'production';
    $this->app->register(NetwatchServiceProvider::class, true);

    $this->get('/netwatch/health', ['Accept' => 'application/json'])
        ->assertForbidden();
});

test('token not configured ignores query param and uses regular auth', function () {
    config(['netwatch.health_route.token' => null]);

    $this->get('/netwatch/health?token=some-token', ['Accept' => 'application/json'])
        ->assertForbidden();
});

test('probe fragment endpoint requires auth', function () {
    config(['netwatch.health_route.token' => 'secret-token']);

    $this->get('/netwatch/health/probes/test-success')
        ->assertForbidden();
});

test('probe fragment endpoint accepts matching token', function () {
    config(['netwatch.health_route.token' => 'secret-token']);

    $this->get('/netwatch/health/probes/test-success?token=secret-token')
        ->assertOk()
        ->assertJsonPath('probe', 'test-success');
});
