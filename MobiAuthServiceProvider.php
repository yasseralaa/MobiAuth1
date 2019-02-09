<?php

namespace Mobidev\Auth;

use Laravel\Passport\Passport;
use Illuminate\Support\ServiceProvider;

class MobiAuthServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        include __DIR__.'/routes.php';
        $this->loadMigrationsFrom(__DIR__.'/migrations');
        Passport::routes();
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->make('Mobidev\Auth\MobiAuthController');
    }
}
