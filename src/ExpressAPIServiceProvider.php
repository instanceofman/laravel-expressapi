<?php


namespace Isofman\LaravelExpressAPI;


use Illuminate\Support\ServiceProvider as BaseProvider;

class ExpressAPIServiceProvider extends BaseProvider
{
    public function register()
    {
        $this->app->singleton('express-api', function() {
            return new ExpressAPI;
        });
    }

    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/routes.php');
    }
}