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
            $ai = new AdminInterface(
                connection_string: config('haproxyadmin.connection_string'),
                backend_state_path: config('haproxyadmin.backend_state_path'),
            );
            $ai->setBackendDefaultoptions(config('haproxyadmin.backend_defaultoptions'));

            return $ai;
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
