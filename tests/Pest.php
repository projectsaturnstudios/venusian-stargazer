<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Stargazer has no application in this suite. Tests run against plain
| objects and Http fakes on a Factory that carries a real EventLoop, so
| async() answers the same loop promise a sketch sees. Do not make live
| NASA calls from Pest.
|
| The app() polyfill below backs the DTO link-followers (render/fetch):
| stargazerHttp() binds its factory as 'http' so a DTO resolves it the
| way it does inside a sketch.
|
*/

use ProjectSaturnStudios\Stargazer\NasaClient;
use Voyager\Config\Repository as Config;
use Voyager\Http\Async\HttpAsyncManager;
use Voyager\Http\Client\Factory;
use Voyager\IOPools\EventLoop;
use Voyager\Vessel\ControlPanel;

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

function stargazerFixture(string $family, string $name): array
{
    $path = __DIR__.'/Fixtures/'.$family.'/'.$name.'.json';
    $decoded = json_decode((string) file_get_contents($path), true);

    if (! is_array($decoded)) {
        throw new RuntimeException('Fixture is missing or not JSON: '.$path);
    }

    return $decoded;
}

/**
 * A stray-proof Http factory. With $loop it rides a fresh EventLoop, so
 * async() works and wait() borrows that loop. It is also bound as 'http'
 * for the app() polyfill.
 */
function stargazerHttp(bool $loop = true): Factory
{
    $vessel = new ControlPanel;
    $vessel->registerInstance('config', new Config(['http' => ['async' => [
        'default' => 'curl',
        'drivers' => ['curl' => ['driver' => 'curl']],
    ]]]));

    if ($loop) {
        $vessel->registerInstance('event-loop', new EventLoop);
    }

    $http = new Factory(null, new HttpAsyncManager($vessel));
    $http->preventStrayRequests();

    $GLOBALS['__stargazer_test_bindings'] = ['http' => $http];

    return $http;
}

function stargazerClient(Factory $http): NasaClient
{
    return new NasaClient(api_key: 'TEST_KEY', http: $http);
}

if (! function_exists('app')) {
    /**
     * Test polyfill: stargazer ships no container, but DTO link-followers
     * resolve the Http factory through app('http') inside a sketch. Tests
     * bind theirs via stargazerHttp().
     */
    function app(?string $abstract = null): mixed
    {
        $bindings = $GLOBALS['__stargazer_test_bindings'] ?? [];

        if (is_null($abstract)) {
            return new class($bindings) {
                public function __construct(private array $bindings) {}

                public function bound(string $abstract): bool
                {
                    return array_key_exists($abstract, $this->bindings);
                }
            };
        }

        if (! array_key_exists($abstract, $bindings)) {
            throw new RuntimeException("Nothing bound as '{$abstract}' — call stargazerHttp() first.");
        }

        return $bindings[$abstract];
    }
}
