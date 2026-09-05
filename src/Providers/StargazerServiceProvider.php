<?php

namespace ProjectSaturnStudios\Stargazer\Providers;

use ProjectSaturnStudios\Stargazer\NasaClient;
use Voyager\Http\Client\Factory;
use Voyager\NutsAndBolts\ServiceProvider;

class StargazerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            dirname(__DIR__, 2).'/config/nasa.php',
            'nasa',
        );

        $this->app->singleton('nasa', function ($app) {
            $api_key = config('nasa.api_key', 'DEMO_KEY');
            $http_client = app(Factory::class);
            $io_pool = app('io-pool');

            return new NasaClient($api_key, $http_client, $io_pool);
        });
    }

    public function boot(): void
    {
        $this->publishes([
            dirname(__DIR__, 2).'/config/nasa.php' => $this->app->configPath('nasa.php'),
        ], 'nasa-config');
    }
}
