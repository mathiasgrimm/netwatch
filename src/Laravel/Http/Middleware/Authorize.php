<?php

declare(strict_types=1);

namespace Mathiasgrimm\Netwatch\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Mathiasgrimm\Netwatch\Netwatch;

class Authorize
{
    public function handle(Request $request, Closure $next)
    {
        $token = config('netwatch.health_route.token');

        if ($token && $request->query('token') && hash_equals($token, $request->query('token'))) {
            return $next($request);
        }

        $callback = Netwatch::authUsing();

        if ($callback && ! $callback($request)) {
            abort(403);
        }

        return $next($request);
    }
}
