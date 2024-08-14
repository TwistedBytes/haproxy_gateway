<?php

namespace App\Providers;

use App\Lib\Haproxy\AdminInterface;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class HaproxyAdminProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(AdminInterface::class, function (Application $app) {

            return new AdminInterface(
                connection_string: config('haproxyadmin.connection_string'),
                state_path: config('haproxyadmin.state_path'),
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
