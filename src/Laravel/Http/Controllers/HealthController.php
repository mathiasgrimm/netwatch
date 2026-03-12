<?php

declare(strict_types=1);

namespace Mathiasgrimm\Netwatch\Laravel\Http\Controllers;

use Illuminate\Http\Request;
use Mathiasgrimm\Netwatch\Netwatch;

class HealthController
{
    public function __invoke(Request $request)
    {
        $netwatch = app(Netwatch::class);

        $probeNames = $request->query('probes')
            ? array_map('trim', explode(',', $request->query('probes')))
            : null;

        $results = $netwatch->run($probeNames);

        $withoutResults = $request->boolean('without_results');

        $data = [];
        $totalFailures = 0;
        $probesWithFailures = 0;
        $totalProbes = 0;

        foreach ($results as $name => $result) {
            $data[$name] = $result->toArray($withoutResults);
            $totalFailures += $result->failures;
            $totalProbes++;
            if ($result->failures > 0) {
                $probesWithFailures++;
            }
        }

        if ($this->shouldReturnHtml($request)) {
            $overallStatus = $totalFailures === 0
                ? 'healthy'
                : ($probesWithFailures === $totalProbes ? 'unhealthy' : 'degraded');

            // Collect disabled probe names from the raw config
            $disabledProbes = [];
            foreach (config('netwatch.probes', []) as $name => $probeConfig) {
                $enabled = $probeConfig['enabled'] ?? false;
                if (! $enabled && ! isset($data[$name])) {
                    $disabledProbes[] = $name;
                }
            }

            // Always build full data (with results) for the JSON panel in the HTML view
            $jsonData = [];
            foreach ($results as $name => $result) {
                $jsonData[$name] = $result->toArray();
            }

            return response(view('netwatch::health', [
                'results' => $data,
                'disabledProbes' => $disabledProbes,
                'jsonData' => json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'checkedAt' => now()->toIso8601String(),
                'overallStatus' => $overallStatus,
            ]));
        }

        return response()->json($data);
    }

    private function shouldReturnHtml(Request $request): bool
    {
        $format = $request->query('format');
        if ($format === 'html') {
            return true;
        }
        if ($format === 'json') {
            return false;
        }

        return str_contains($request->header('Accept', ''), 'text/html')
            && $request->prefers(['text/html', 'application/json']) === 'text/html';
    }
}
