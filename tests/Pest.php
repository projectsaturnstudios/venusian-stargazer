<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Stargazer has no application container in this suite. Tests run against
| plain objects, Http::fake() fixtures, and IOPools fake drivers. Do not
| make live NASA calls from Pest.
|
*/

use ProjectSaturnStudios\Stargazer\NasaClient;
use Voyager\Contracts\IOPools\HttpDriver;
use Voyager\Http\Client\Factory;
use Voyager\IOPools\EventQueue;
use Voyager\IOPools\HttpPool;
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

function stargazerPool(): array
{
    $driver = new class implements HttpDriver
    {
        public array $dispatched = [];

        public function dispatch(string $name, string $method, string $url, array $headers, ?string $body): void
        {
            $this->dispatched[] = compact('name', 'method', 'url', 'headers', 'body');
        }

        public function harvest(): array
        {
            return [];
        }

        public function progress(): array
        {
            return [];
        }
    };

    return [$driver, new HttpPool($driver, new EventQueue)];
}

function stargazerClient(Factory $http, ?HttpPool $pool = null): NasaClient
{
    return new NasaClient(api_key: 'TEST_KEY', http: $http, pool: $pool);
}
