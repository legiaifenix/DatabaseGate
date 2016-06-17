<?php

namespace LegiaiFenix\DatabaseGate;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\ServiceProvider;

class DataBaseGateProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {

    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('LegiaiFenix\DatabaseGate\DatabaseService', function($app) {
           return new DatabaseService($app['DatabaseManager'], $app['Log']);
        });
    }
}
