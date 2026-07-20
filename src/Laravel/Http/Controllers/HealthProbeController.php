<?php

declare(strict_types=1);

namespace MathiasGrimm\Netwatch\Laravel\Http\Controllers;

use MathiasGrimm\Netwatch\Netwatch;

class HealthProbeController
{
    public function __invoke(string $probe)
    {
        $netwatch = app(Netwatch::class);

        if (! in_array($probe, $netwatch->probeNames(), true)) {
            abort(404, "Probe not found: {$probe}");
        }

        $result = $netwatch->run($probe)[$probe]->toArray();

        return response()->json([
            'probe' => $probe,
            'checked_at' => now()->toIso8601String(),
            'html' => view('netwatch::partials.card', ['name' => $probe, 'result' => $result])->render(),
            'result' => $result,
        ]);
    }
}
