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

test('token not configured ignores query param and uses regular auth', function () {
    config(['netwatch.health_route.token' => null]);

    $this->get('/netwatch/health?token=some-token', ['Accept' => 'application/json'])
        ->assertForbidden();
});
