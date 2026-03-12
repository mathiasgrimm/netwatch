<?php

declare(strict_types=1);

namespace Mathiasgrimm\Netwatch\Laravel;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Mathiasgrimm\Netwatch\Netwatch;

class NetwatchApplicationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->authorization();
    }

    protected function authorization(): void
    {
        $this->gate();

        Netwatch::auth(function ($request) {
            return app()->environment('local')
                || Gate::check('viewNetwatch', [$request->user()]);
        });
    }

    protected function gate(): void
    {
        Gate::define('viewNetwatch', function ($user = null) {
            return in_array(optional($user)->email, [
                //
            ]);
        });
    }
}
