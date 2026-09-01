<?php

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\APOD\ApodAPIService;
use ProjectSaturnStudios\Stargazer\DONKI\DonkiAPIService;
use ProjectSaturnStudios\Stargazer\EONET\EonetAPIService;
use ProjectSaturnStudios\Stargazer\EPIC\EpicAPIService;
use ProjectSaturnStudios\Stargazer\Enums\NasaURL;
use ProjectSaturnStudios\Stargazer\ImageLibrary\ImageLibraryAPIService;
use ProjectSaturnStudios\Stargazer\InSight\InsightAPIService;
use ProjectSaturnStudios\Stargazer\NeoWs\NeowsAPIService;
use ProjectSaturnStudios\Stargazer\TLE\TleAPIService;
use ProjectSaturnStudios\Stargazer\TechTransfer\TechTransferAPIService;
use ProjectSaturnStudios\Stargazer\Exceptions\NotYetSupportedException;
use ProjectSaturnStudios\Stargazer\Exceptions\StargazerException;
use ProjectSaturnStudios\Stargazer\NasaApiService;
use ProjectSaturnStudios\Stargazer\NasaClient;
use ProjectSaturnStudios\Stargazer\PendingNasaRequest;
use Voyager\Contracts\IOPools\HttpDriver;
use Voyager\Http\Client\Factory;
use Voyager\IOPools\EventQueue;
use Voyager\IOPools\HttpPool;
use Voyager\IOPools\PendingCall;
use Voyager\NutsAndBolts\Collection;
use Voyager\NutsAndBolts\MagicAliases\Http;

final readonly class CoreArchSampleRecord implements HydratesFromArray
{
    public function __construct(
        public string $id,
        public string $name,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            id: (string) $data['id'],
            name: (string) $data['name'],
        );
    }
}

function coreArchHttp(): Factory
{
    $http = new Factory;
    $http->preventStrayRequests();
    Http::swap($http);

    return $http;
}

function coreArchPool(): array
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

beforeEach(function () {
    Http::clearResolvedInstances();
});

afterEach(function () {
    Http::clearResolvedInstances();
});

it('exposes NotYetSupportedException as a StargazerException', function () {
    expect(NotYetSupportedException::forApi('GIBS'))
        ->toBeInstanceOf(StargazerException::class);
});

it('exposes every core API accessor on NasaClient', function () {
    $client = new NasaClient(api_key: 'TEST_KEY');

    expect($client->donki())->toBeInstanceOf(DonkiAPIService::class)
        ->and($client->neows())->toBeInstanceOf(NeowsAPIService::class)
        ->and($client->eonet())->toBeInstanceOf(EonetAPIService::class)
        ->and($client->apod())->toBeInstanceOf(ApodAPIService::class)
        ->and($client->epic())->toBeInstanceOf(EpicAPIService::class)
        ->and($client->insight())->toBeInstanceOf(InsightAPIService::class)
        ->and($client->tle())->toBeInstanceOf(TleAPIService::class)
        ->and($client->techtransfer())->toBeInstanceOf(TechTransferAPIService::class)
        ->and($client->imageLibrary())->toBeInstanceOf(ImageLibraryAPIService::class)
        ->and($client->donki())->toBeInstanceOf(NasaApiService::class);
});

it('hydrates a list endpoint into a Collection of DTOs via get()', function () {
    $http = coreArchHttp();
    $http->fake(function () {
        return Factory::response([
            ['id' => 'a', 'name' => 'alpha'],
            ['id' => 'b', 'name' => 'beta'],
        ]);
    });

    $result = (new NasaClient(api_key: 'TEST_KEY', http: $http))
        ->pending(
            base: NasaURL::DONKI,
            path: 'CME',
            call_name: 'stargazer.donki.cme',
            hydrator: CoreArchSampleRecord::class,
            query: ['startDate' => '2026-07-01', 'endDate' => '2026-08-01'],
        )
        ->get();

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result)->toHaveCount(2)
        ->and($result->first())->toBeInstanceOf(CoreArchSampleRecord::class)
        ->and($result->first()->id)->toBe('a')
        ->and($result->last()->name)->toBe('beta');

    $http->assertSent(function ($request) {
        $url = $request->url();

        return str_contains($url, '/DONKI/CME')
            && str_contains($url, 'startDate=2026-07-01')
            && str_contains($url, 'api_key=TEST_KEY');
    });
});

it('hydrates an object payload into a single DTO via fromArray()', function () {
    $http = coreArchHttp();
    $http->fake(fn () => Factory::response(['id' => 'solo', 'name' => 'one']));

    $result = (new PendingNasaRequest(
        base: NasaURL::APOD,
        path: '',
        call_name: 'stargazer.apod.show',
        hydrator: CoreArchSampleRecord::class,
        api_key: 'TEST_KEY',
        http: $http,
    ))->get();

    expect($result)->toBeInstanceOf(CoreArchSampleRecord::class)
        ->and($result->id)->toBe('solo');
});

it('appends api_key only for api.nasa.gov hosts', function () {
    $http = coreArchHttp();
    $http->fake(fn () => Factory::response(['id' => '1', 'name' => 'open']));

    (new PendingNasaRequest(
        base: NasaURL::EONET,
        path: 'categories',
        call_name: 'stargazer.eonet.categories',
        hydrator: CoreArchSampleRecord::class,
        query: ['source' => 'InciWeb', 'status' => 'open'],
        api_key: 'TEST_KEY',
        http: $http,
    ))->get();

    $http->assertSent(function ($request) {
        $url = $request->url();

        return str_contains($url, '/categories')
            && str_contains($url, 'source=InciWeb')
            && ! str_contains($url, 'api_key=');
    });
});

it('returns a namespaced PendingCall from async() when a pool is bound', function () {
    $http = coreArchHttp();
    [$driver, $pool] = coreArchPool();

    $call = (new PendingNasaRequest(
        base: NasaURL::DONKI,
        path: 'CME',
        call_name: 'stargazer.donki.cme',
        hydrator: CoreArchSampleRecord::class,
        query: ['startDate' => '2026-07-01'],
        api_key: 'TEST_KEY',
        http: $http,
        pool: $pool,
    ))->async();

    expect($call)->toBeInstanceOf(PendingCall::class)
        ->and($call->name)->toBe('stargazer.donki.cme')
        ->and($driver->dispatched[0]['method'])->toBe('GET')
        ->and($driver->dispatched[0]['url'])->toContain('/DONKI/CME')
        ->and($driver->dispatched[0]['url'])->toContain('api_key=TEST_KEY');
});

it('throws StargazerException from async() when no HttpPool is bound', function () {
    $http = coreArchHttp();

    expect(fn () => (new PendingNasaRequest(
        base: NasaURL::DONKI,
        path: 'CME',
        call_name: 'stargazer.donki.cme',
        hydrator: CoreArchSampleRecord::class,
        http: $http,
    ))->async())->toThrow(StargazerException::class, 'HttpPool');
});

it('throws StargazerException when a sync request is not successful', function () {
    $http = coreArchHttp();
    $http->fake(fn () => Factory::response(['error' => 'nope'], 500));

    expect(fn () => (new PendingNasaRequest(
        base: NasaURL::DONKI,
        path: 'CME',
        call_name: 'stargazer.donki.cme',
        hydrator: CoreArchSampleRecord::class,
        api_key: 'TEST_KEY',
        http: $http,
    ))->get())->toThrow(StargazerException::class);
});

it('hydrates through a closure when the caller supplies one', function () {
    $http = coreArchHttp();
    $http->fake(fn () => Factory::response(['id' => 'z', 'name' => 'zeta']));

    $result = (new PendingNasaRequest(
        base: NasaURL::APOD,
        path: '',
        call_name: 'stargazer.apod.show',
        hydrator: fn (array $data) => CoreArchSampleRecord::fromArray($data),
        api_key: 'TEST_KEY',
        http: $http,
    ))->get();

    expect($result)->toBeInstanceOf(CoreArchSampleRecord::class)
        ->and($result->name)->toBe('zeta');
});

it('falls back to DEMO_KEY when no api_key is given for an api.nasa.gov host', function () {
    $http = coreArchHttp();
    $http->fake(fn () => Factory::response(['id' => '1', 'name' => 'n']));

    (new PendingNasaRequest(
        base: NasaURL::DONKI,
        path: 'FLR',
        call_name: 'stargazer.donki.flr',
        hydrator: CoreArchSampleRecord::class,
        http: $http,
    ))->get();

    $http->assertSent(fn ($request) => str_contains($request->url(), 'api_key=DEMO_KEY'));
});

it('lets fluent with() replace query params on the pending request', function () {
    $http = coreArchHttp();
    $http->fake(fn () => Factory::response(['id' => '1', 'name' => 'n']));

    (new PendingNasaRequest(
        base: NasaURL::EONET,
        path: 'events',
        call_name: 'stargazer.eonet.events',
        hydrator: CoreArchSampleRecord::class,
        http: $http,
    ))->with('source', 'InciWeb')->with('status', 'open')->get();

    $http->assertSent(function ($request) {
        $url = $request->url();

        return str_contains($url, 'source=InciWeb')
            && str_contains($url, 'status=open');
    });
});
