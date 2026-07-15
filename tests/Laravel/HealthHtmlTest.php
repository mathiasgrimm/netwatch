<?php

declare(strict_types=1);

use Mathiasgrimm\Netwatch\Laravel\NetwatchServiceProvider;
use Mathiasgrimm\Netwatch\Netwatch;
use Mathiasgrimm\Netwatch\Tests\Fixtures\CountingProbe;
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

test('HTML initial render shows checking badge and skeleton cards', function () {
    $response = $this->get('/netwatch/health?format=html');

    $content = $response->getContent();
    expect($content)
        ->toContain('badge-checking')
        ->toContain('>checking<')
        ->toContain('card-skeleton')
        ->toContain('data-probe="test-success"')
        ->toContain('data-probe="test-failing"')
        ->toContain('NETWATCH_PROBES');
});

test('HTML initial render contains no probe results', function () {
    $response = $this->get('/netwatch/health?format=html');

    expect($response->getContent())
        ->not->toContain('Total p95')
        ->not->toContain('<table>');
});

test('HTML render does not execute probes', function () {
    CountingProbe::$calls = 0;
    config([
        'netwatch.probes' => [
            'test-counting' => ['enabled' => true, 'probe' => new CountingProbe],
        ],
    ]);
    $this->app->forgetInstance(Netwatch::class);

    $this->get('/netwatch/health?format=html')->assertOk();
    expect(CountingProbe::$calls)->toBe(0);

    $this->get('/netwatch/health/probes/test-counting')->assertOk();
    expect(CountingProbe::$calls)->toBe(2);
});

test('HTML shows empty state with healthy badge when no probes enabled', function () {
    config(['netwatch.probes' => []]);
    $this->app->forgetInstance(Netwatch::class);

    $response = $this->get('/netwatch/health?format=html');

    expect($response->getContent())
        ->toContain('No probes configured')
        ->toContain('badge-healthy');
});

test('HTML includes the auto-refresh toggle switched off by default', function () {
    $response = $this->get('/netwatch/health?format=html');

    expect($response->getContent())
        ->toContain('id="btn-auto-refresh"')
        ->toContain('aria-pressed="false"')
        ->toContain('netwatch-auto-refresh');
});

test('HTML exposes latency thresholds from config', function () {
    config([
        'netwatch.probes' => [
            'test-success' => [
                'enabled' => true,
                'probe' => new SuccessProbe,
                'thresholds' => ['warn' => 5, 'crit' => 25],
            ],
        ],
    ]);
    $this->app->forgetInstance(Netwatch::class);

    $content = $this->get('/netwatch/health?format=html')->getContent();

    expect($content)
        ->toContain('NETWATCH_THRESHOLDS')
        ->toContain('"warn":5')
        ->toContain('"crit":25');
});

test('HTML thresholds are null for custom probes without thresholds', function () {
    // The beforeEach probes define no thresholds key and their names have no
    // package default to fall back to, so both budgets stay disabled.
    $content = $this->get('/netwatch/health?format=html')->getContent();

    expect($content)->toContain('"warn":null')
        ->toContain('"crit":null');
});

test('HTML thresholds fall back to package defaults when the key is absent', function () {
    // Simulates a config file published before thresholds existed: the probe
    // key matches a built-in probe but carries no thresholds entry.
    config([
        'netwatch.probes' => [
            'database' => ['enabled' => true, 'probe' => new SuccessProbe],
        ],
    ]);
    $this->app->forgetInstance(Netwatch::class);

    $content = $this->get('/netwatch/health?format=html')->getContent();

    expect($content)
        ->toContain('"warn":10')
        ->toContain('"crit":25');
});

test('HTML thresholds are disabled when explicitly set to null', function () {
    config([
        'netwatch.probes' => [
            'database' => ['enabled' => true, 'probe' => new SuccessProbe, 'thresholds' => null],
        ],
    ]);
    $this->app->forgetInstance(Netwatch::class);

    $content = $this->get('/netwatch/health?format=html')->getContent();

    expect($content)->toContain('"warn":null')
        ->toContain('"crit":null');
});

test('HTML includes the status summary element', function () {
    $content = $this->get('/netwatch/health?format=html')->getContent();

    expect($content)->toContain('id="status-summary"');
});

test('JSON API includes status, thresholds and per-sample statuses', function () {
    config([
        'netwatch.probes' => [
            // SuccessProbe totals 3.0 ms; warn 2.5 puts it over warn only
            'test-success' => [
                'enabled' => true,
                'probe' => new SuccessProbe,
                'thresholds' => ['warn' => 2.5, 'crit' => 10],
            ],
            'test-failing' => ['enabled' => true, 'probe' => new FailingProbe, 'thresholds' => null],
        ],
    ]);
    $this->app->forgetInstance(Netwatch::class);

    $this->get('/netwatch/health?format=json')
        ->assertOk()
        ->assertJsonPath('test-success.status', 'warn')
        ->assertJsonPath('test-success.thresholds.warn', 2.5)
        ->assertJsonPath('test-success.thresholds.crit', 10)
        ->assertJsonPath('test-success.over_warn', 2)
        ->assertJsonPath('test-success.over_crit', 0)
        ->assertJsonPath('test-success.results.0.status', 'warn')
        ->assertJsonPath('test-failing.status', 'failing')
        ->assertJsonPath('test-failing.thresholds.warn', null);
});

test('HTML includes export image button and script', function () {
    $response = $this->get('/netwatch/health?format=html');

    $content = $response->getContent();
    expect($content)
        ->toContain('onclick="exportImage()"')
        ->toContain('Export image')
        ->toContain('function exportImage')
        ->toContain('NETWATCH_META')
        ->toContain("'image/webp'");
});

test('probes query parameter filters to only specified probes in JSON', function () {
    $this->get('/netwatch/health?probes=test-success', ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonStructure(['test-success'])
        ->assertJsonMissing(['name' => 'failing-probe']);
});

test('HTML includes the theme switcher', function () {
    $response = $this->get('/netwatch/health?format=html');

    $content = $response->getContent();
    expect($content)
        ->toContain('id="theme-toggle"')
        ->toContain('netwatch-theme')
        ->toContain('aria-label="Switch theme"')
        ->toContain('window.toggleTheme');
});

test('probes query parameter works with HTML format', function () {
    $response = $this->get('/netwatch/health?probes=test-success&format=html');

    $content = $response->getContent();
    expect($content)
        ->toContain('test-success')
        ->not->toContain('test-failing');
});
