<?php

namespace ProjectSaturnStudios\Stargazer\Providers;

use ProjectSaturnStudios\Stargazer\NasaClient;
use Voyager\NutsAndBolts\ServiceProvider;

class StargazerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            dirname(__DIR__, 2).'/config/nasa.php',
            'nasa',
        );

        $this->app->singleton(NasaClient::class, function ($app) {
            $api_key = 'DEMO_KEY';

            if ($app->bound('config')) {
                $configured = $app['config']->get('nasa.api_key');
                if (! is_null($configured) && $configured !== '') {
                    $api_key = (string) $configured;
                }
            }

            return new NasaClient(api_key: $api_key);
        });
    }

    public function boot(): void
    {
        $this->publishes([
            dirname(__DIR__, 2).'/config/nasa.php' => $this->app->configPath('nasa.php'),
        ], 'nasa-config');
    }
}
