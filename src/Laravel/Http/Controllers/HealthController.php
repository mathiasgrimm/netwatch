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

        if ($this->shouldReturnHtml($request)) {
            // The HTML dashboard runs no probes: it renders skeleton cards
            // and fetches each probe from the per-probe endpoint.
            $selected = $probeNames === null
                ? $netwatch->probeNames()
                : array_values(array_intersect($probeNames, $netwatch->probeNames()));

            // Collect disabled probe names from the raw config
            $disabledProbes = [];
            foreach (config('netwatch.probes', []) as $name => $probeConfig) {
                $enabled = $probeConfig['enabled'] ?? false;
                if (! $enabled && ! in_array($name, $selected, true)) {
                    $disabledProbes[] = $name;
                }
            }

            // Thresholds resolved by the Netwatch singleton (provider applies
            // package defaults for configs missing the thresholds key)
            $thresholds = array_intersect_key($netwatch->thresholds(), array_flip($selected));

            return response(view('netwatch::health', [
                'probeNames' => $selected,
                'thresholds' => $thresholds,
                'disabledProbes' => $disabledProbes,
                'checkedAt' => now()->toIso8601String(),
                'overallStatus' => $selected === [] ? 'healthy' : 'checking',
                // Defaults for previously published copies of the old view
                'results' => [],
                'jsonData' => '{}',
            ]));
        }

        $results = $netwatch->run($probeNames);

        $withoutResults = $request->boolean('without_results');

        $data = [];
        foreach ($results as $name => $result) {
            $data[$name] = $result->toArray($withoutResults);
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
