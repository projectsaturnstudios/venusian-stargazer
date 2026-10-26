<?php

namespace ProjectSaturnStudios\Stargazer\Providers;

use ReflectionException;
use Voyager\NutsAndBolts\ServiceProvider;
use ProjectSaturnStudios\Stargazer\NasaClient;

class StargazerServiceProvider extends ServiceProvider
{
    /**
     * @throws ReflectionException
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            dirname(__DIR__, 2).'/config/nasa.php',
            'nasa',
        );

        $this->app->registerSingleton('nasa', fn ($app) => new NasaClient(
            api_key: $app['config']->get('nasa.api_key', 'DEMO_KEY'),
            http: $app['http'],
        ));
    }

    public function boot(): void
    {
        $this->publishes([
            dirname(__DIR__, 2).'/config/nasa.php' => $this->app->configPath('nasa.php'),
        ], 'nasa-config');
    }
}
