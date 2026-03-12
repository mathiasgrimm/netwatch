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
        $callback = Netwatch::authUsing();

        if ($callback && ! $callback($request)) {
            abort(403);
        }

        return $next($request);
    }
}
