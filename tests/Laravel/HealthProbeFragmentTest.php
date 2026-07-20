<?php

declare(strict_types=1);

use MathiasGrimm\Netwatch\Laravel\NetwatchServiceProvider;
use MathiasGrimm\Netwatch\Netwatch;
use MathiasGrimm\Netwatch\Tests\Fixtures\FailingProbe;
use MathiasGrimm\Netwatch\Tests\Fixtures\SuccessProbe;
use Orchestra\Testbench\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->app->register(NetwatchServiceProvider::class);

    config([
        'netwatch.health_route.enabled' => true,
        'netwatch.health_route.middleware' => [],
        'netwatch.iterations' => 2,
        'netwatch.probes' => [
            'test-success' => ['enabled' => true, 'probe' => new SuccessProbe],
            'test-failing' => ['enabled' => true, 'probe' => new FailingProbe],
            'test-disabled' => ['enabled' => false, 'probe' => new SuccessProbe],
        ],
    ]);

    $this->app->forgetInstance(Netwatch::class);
    $this->app->register(NetwatchServiceProvider::class, true);
});

test('returns probe result with rendered card html', function () {
    $response = $this->get('/netwatch/health/probes/test-success');

    $response->assertOk()
        ->assertJsonStructure([
            'probe',
            'checked_at',
            'html',
            'result' => ['name', 'iterations', 'stats', 'failures', 'results'],
        ])
        ->assertJsonPath('probe', 'test-success')
        ->assertJsonPath('result.iterations', 2)
        ->assertJsonPath('result.failures', 0);
});

test('html fragment contains the card markup', function () {
    $html = $this->get('/netwatch/health/probes/test-success')->json('html');

    expect($html)
        ->toContain('data-probe="test-success"')
        ->toContain('badge-healthy')
        ->toContain('Connect')
        ->toContain('Request')
        ->toContain('Total')
        ->toContain('stat-value')
        ->toContain('data-metrics-toggle')
        ->toContain('row-detail')
        ->toContain('row-total');
});

test('crit breach renders critical badge instead of healthy', function () {
    config([
        'netwatch.probes' => [
            // SuccessProbe totals 3.0 ms; crit 2 puts it over the crit threshold
            'test-success' => [
                'enabled' => true,
                'probe' => new SuccessProbe,
                'thresholds' => ['warn' => 1, 'crit' => 2],
            ],
        ],
    ]);
    $this->app->forgetInstance(Netwatch::class);

    $response = $this->get('/netwatch/health/probes/test-success');

    $response->assertOk()->assertJsonPath('result.status', 'crit');
    expect($response->json('html'))
        ->toContain('badge-unhealthy')
        ->toContain('critical')
        ->toContain('card-failing')
        ->not->toContain('badge-healthy');
});

test('failing probe fragment renders failure card', function () {
    $response = $this->get('/netwatch/health/probes/test-failing');

    $response->assertOk();
    expect($response->json('html'))
        ->toContain('card-failing')
        ->toContain('error-list');
    expect($response->json('result.failures'))->toBeGreaterThan(0);
});

test('unknown probe returns 404', function () {
    $this->get('/netwatch/health/probes/nope')->assertNotFound();
});

test('disabled probe returns 404', function () {
    $this->get('/netwatch/health/probes/test-disabled')->assertNotFound();
});

test('url-encoded probe names round-trip', function () {
    config([
        'netwatch.probes' => [
            'my probe.v2' => ['enabled' => true, 'probe' => new SuccessProbe],
        ],
    ]);
    $this->app->forgetInstance(Netwatch::class);

    $this->get('/netwatch/health/probes/my%20probe.v2')
        ->assertOk()
        ->assertJsonPath('probe', 'my probe.v2');
});
