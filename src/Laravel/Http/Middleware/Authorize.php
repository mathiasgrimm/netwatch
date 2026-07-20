<?php

declare(strict_types=1);

namespace MathiasGrimm\Netwatch\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use MathiasGrimm\Netwatch\Netwatch;

class Authorize
{
    public function handle(Request $request, Closure $next)
    {
        $token = config('netwatch.health_route.token');
        $requestToken = $request->query('token');

        if ($token && is_string($requestToken) && $requestToken !== '' && hash_equals($token, $requestToken)) {
            return $next($request);
        }

        $callback = Netwatch::authUsing();

        if ($callback && $callback($request)) {
            return $next($request);
        }

        abort(403);
    }
}
