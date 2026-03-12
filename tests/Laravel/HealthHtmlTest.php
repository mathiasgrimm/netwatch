<?php

declare(strict_types=1);

use Mathiasgrimm\Netwatch\Laravel\NetwatchServiceProvider;
use Mathiasgrimm\Netwatch\Netwatch;
use Mathiasgrimm\Netwatch\Tests\Fixtures\FailingProbe;
use Mathiasgrimm\Netwatch\Tests\Fixtures\SuccessProbe;
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
        ],
    ]);

    $this->app->forgetInstance(Netwatch::class);
    $this->app->register(NetwatchServiceProvider::class, true);
});

test('returns JSON by default with application/json accept header', function () {
    $this->get('/netwatch/health', ['Accept' => 'application/json'])
        ->assertOk()
        ->assertHeader('Content-Type', 'application/json')
        ->assertJsonStructure(['test-success', 'test-failing']);
});

test('returns HTML when browser Accept header is sent', function () {
    $response = $this->get('/netwatch/health', [
        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
    ]);

    $response->assertOk();
    $content = $response->getContent();
    expect($content)->toContain('<!DOCTYPE html')
        ->toContain('Netwatch Health Dashboard');
});

test('format=html forces HTML regardless of Accept header', function () {
    $response = $this->get('/netwatch/health?format=html', [
        'Accept' => 'application/json',
    ]);

    $response->assertOk();
    expect($response->getContent())->toContain('<!DOCTYPE html');
});

test('format=json forces JSON even with text/html Accept header', function () {
    $this->get('/netwatch/health?format=json', [
        'Accept' => 'text/html',
    ])
        ->assertOk()
        ->assertHeader('Content-Type', 'application/json')
        ->assertJsonStructure(['test-success', 'test-failing']);
});

test('returns JSON for curl default Accept header', function () {
    $this->get('/netwatch/health', ['Accept' => '*/*'])
        ->assertOk()
        ->assertHeader('Content-Type', 'application/json')
        ->assertJsonStructure(['test-success']);
});

test('HTML shows healthy status when zero failures', function () {
    config([
        'netwatch.probes' => [
            'test-success' => ['enabled' => true, 'probe' => new SuccessProbe],
        ],
    ]);
    $this->app->forgetInstance(Netwatch::class);

    $response = $this->get('/netwatch/health?format=html');

    expect($response->getContent())
        ->toContain('badge-healthy')
        ->toContain('>healthy<');
});

test('HTML shows unhealthy status when all probes fail', function () {
    config([
        'netwatch.probes' => [
            'test-failing' => ['enabled' => true, 'probe' => new FailingProbe],
        ],
    ]);
    $this->app->forgetInstance(Netwatch::class);

    $response = $this->get('/netwatch/health?format=html');

    expect($response->getContent())
        ->toContain('badge-unhealthy')
        ->toContain('>unhealthy<');
});

test('HTML shows probe stats table with metric labels', function () {
    $response = $this->get('/netwatch/health?format=html');

    $content = $response->getContent();
    expect($content)
        ->toContain('Connect')
        ->toContain('Request')
        ->toContain('Total')
        ->toContain('Min')
        ->toContain('Max')
        ->toContain('Avg')
        ->toContain('P50')
        ->toContain('P95')
        ->toContain('P99');
});

test('probes query parameter filters to only specified probes in JSON', function () {
    $this->get('/netwatch/health?probes=test-success', ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonStructure(['test-success'])
        ->assertJsonMissing(['name' => 'failing-probe']);
});

test('probes query parameter works with HTML format', function () {
    $response = $this->get('/netwatch/health?probes=test-success&format=html');

    $content = $response->getContent();
    expect($content)
        ->toContain('test-success')
        ->not->toContain('test-failing');
});
