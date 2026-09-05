<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Stargazer has no application container in this suite. Tests run against
| plain objects, Http::fake() fixtures, and a bare IOPoolDock with a fake
| curl driver registered directly. Do not make live NASA calls from Pest.
|
| The app() polyfill below backs the DTO link-followers (renderAsync):
| stargazerDock() binds its dock as 'io-pool' so a DTO can resolve the
| pool exactly the way it does inside a sketch.
|
*/

use ProjectSaturnStudios\Stargazer\NasaClient;
use Tests\Support\FakeCurlDriver;
use Tests\Support\FakeVessel;
use Voyager\Contracts\IOPools\PoolService;
use Voyager\Http\Client\Factory;
use Voyager\IOPools\DTO\HttpResult;
use Voyager\IOPools\IOPoolDock;
use Voyager\NutsAndBolts\MagicAliases\Http;

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

function stargazerHttp(): Factory
{
    $http = new Factory;
    $http->preventStrayRequests();
    Http::swap($http);

    return $http;
}

/**
 * A bare dock with a recording curl driver registered as 'http', also
 * bound as 'io-pool' for the app() polyfill so DTO link-followers resolve
 * it the way they would inside a sketch.
 *
 * @return array{IOPoolDock, FakeCurlDriver}
 */
function stargazerDock(): array
{
    $dock = new IOPoolDock(new FakeVessel, ['resources' => []]);
    $driver = new FakeCurlDriver($dock);
    $dock->resource('http', $driver);

    $GLOBALS['__stargazer_test_bindings'] = ['io-pool' => $dock];

    return [$dock, $driver];
}

function stargazerClient(Factory $http, ?PoolService $pool = null): NasaClient
{
    return new NasaClient(api_key: 'TEST_KEY', http: $http, io_pool: $pool);
}

/**
 * Stage a completed transport conversation on the fake driver.
 */
function stargazerResult(string $name, mixed $payload, bool $ok = true, int $status = 200, ?string $error = null): HttpResult
{
    return new HttpResult(
        name: $name,
        ok: $ok,
        status: $status,
        headers: [],
        body: is_string($payload) ? $payload : json_encode($payload),
        error: $error,
    );
}

if (! function_exists('app')) {
    /**
     * Test polyfill: stargazer ships no container, but DTO link-followers
     * resolve the pool through app('io-pool') inside a sketch. Tests bind
     * theirs via stargazerDock().
     */
    function app(?string $abstract = null): mixed
    {
        $bindings = $GLOBALS['__stargazer_test_bindings'] ?? [];

        if (is_null($abstract)) {
            throw new RuntimeException('The test app() polyfill resolves named bindings only.');
        }

        if (! array_key_exists($abstract, $bindings)) {
            throw new RuntimeException("Nothing bound as '{$abstract}' — call stargazerDock() first.");
        }

        return $bindings[$abstract];
    }
}
