<?php

use ProjectSaturnStudios\Stargazer\NasaClient;
use ProjectSaturnStudios\Stargazer\TLE\DataObjects\TleCollection;
use ProjectSaturnStudios\Stargazer\TLE\DataObjects\TleRecord;
use ProjectSaturnStudios\Stargazer\TLE\TleArrived;
use ProjectSaturnStudios\Stargazer\TLE\TleFailed;
use Voyager\Http\Client\Factory;
use Voyager\IOPools\Presumption;
use Voyager\NutsAndBolts\MagicAliases\Http;

function tleFixture(string $file): array
{
    return json_decode(
        file_get_contents(dirname(__DIR__).'/Fixtures/TLE/'.$file),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );
}

function tleHttp(string $file): Factory
{
    $http = new Factory;
    $http->preventStrayRequests();
    $http->fake(fn () => Factory::response(tleFixture($file)));
    Http::swap($http);

    return $http;
}

beforeEach(function () {
    Http::clearResolvedInstances();
});

afterEach(function () {
    Http::clearResolvedInstances();
});

it('builds the TLE collection URL without an api_key', function () {
    $http = tleHttp('collection.json');

    (new NasaClient(api_key: 'TEST_KEY', http: $http))->tle()->collection()->get();

    $http->assertSent(function ($request) {
        $url = $request->url();

        return str_contains($url, 'tle.ivanstanojevic.me/api/tle')
            && ! str_contains($url, 'api_key=');
    });
});

it('hydrates a TLE collection from the captured fixture', function () {
    $payload = tleFixture('collection.json');
    $http = tleHttp('collection.json');

    $page = (new NasaClient(http: $http))->tle()->collection()->get();

    expect($page)->toBeInstanceOf(TleCollection::class)
        ->and($page->totalItems)->toBe($payload['totalItems'])
        ->and($page->context)->toBe($payload['@context'])
        ->and($page->id)->toBe($payload['@id'])
        ->and($page->type)->toBe($payload['@type'])
        ->and($page->members)->toHaveCount(count($payload['member']))
        ->and($page->members->first())->toBeInstanceOf(TleRecord::class)
        ->and($page->members->first()->satelliteId)->toBe($payload['member'][0]['satelliteId'])
        ->and($page->members->first()->name)->toBe($payload['member'][0]['name'])
        ->and($page->members->first()->line1)->toBe($payload['member'][0]['line1'])
        ->and($page->members->first()->line2)->toBe($payload['member'][0]['line2'])
        ->and($page->parameters->search)->toBe($payload['parameters']['search'])
        ->and($page->parameters->sort)->toBe($payload['parameters']['sort'])
        ->and($page->parameters->sortDirection)->toBe($payload['parameters']['sort-dir'])
        ->and($page->parameters->page)->toBe($payload['parameters']['page'])
        ->and($page->parameters->pageSize)->toBe($payload['parameters']['page-size'])
        ->and($page->view->next)->toBe($payload['view']['next'])
        ->and($page->view->last)->toBe($payload['view']['last']);
});

it('searches TLE records by satellite name', function () {
    $payload = tleFixture('search.json');
    $http = tleHttp('search.json');

    $page = (new NasaClient(http: $http))->tle()->search('ISS')->get();

    expect($page)->toBeInstanceOf(TleCollection::class)
        ->and($page->totalItems)->toBe($payload['totalItems'])
        ->and($page->members->first()->name)->toBe('ISS (ZARYA)')
        ->and($page->parameters->search)->toBe('ISS');

    $http->assertSent(function ($request) {
        $url = $request->url();

        return str_contains($url, 'tle.ivanstanojevic.me/api/tle')
            && str_contains($url, 'search=ISS')
            && ! str_contains($url, 'api_key=');
    });
});

it('retrieves a single TLE satellite by NORAD id', function () {
    $payload = tleFixture('satellite.json');
    $http = tleHttp('satellite.json');

    $record = (new NasaClient(http: $http))->tle()->satellite(25544)->get();

    expect($record)->toBeInstanceOf(TleRecord::class)
        ->and($record->satelliteId)->toBe(25544)
        ->and($record->name)->toBe($payload['name'])
        ->and($record->date)->toBe($payload['date'])
        ->and($record->line1)->toBe($payload['line1'])
        ->and($record->line2)->toBe($payload['line2'])
        ->and($record->id)->toBe($payload['@id'])
        ->and($record->type)->toBe($payload['@type']);

    $http->assertSent(fn ($request) => str_contains($request->url(), '/api/tle/25544'));
});

it('dispatches each TLE async() builder under its namespaced call name', function (string $method, array $args, string $path, ?string $query = null) {
    [$dock, $driver] = stargazerDock();

    $presumption = stargazerClient(stargazerHttp(), $dock)->tle()->{$method}(...$args)->async();

    expect($presumption)->toBeInstanceOf(Presumption::class)
        ->and($presumption->name)->toBe('stargazer.tle.'.$method)
        ->and($driver->dispatched[0]['url'])->toContain($path)
        ->and($driver->dispatched[0]['url'])->not->toContain('api_key=');

    if (! is_null($query)) {
        expect($driver->dispatched[0]['url'])->toContain($query);
    }
})->with([
    'collection' => ['collection', [], '/api/tle'],
    'search' => ['search', ['ISS'], '/api/tle', 'search=ISS'],
    'satellite' => ['satellite', [25544], '/api/tle/25544'],
]);

it('mails TleArrived carrying the hydrated page through the dock', function () {
    [$dock, $driver] = stargazerDock();

    $presumption = stargazerClient(stargazerHttp(), $dock)->tle()->collection()->async();

    $driver->ready = [stargazerResult('stargazer.tle.collection', stargazerFixture('TLE', 'collection'))];
    $dock->pump();

    $mail = $dock->drain()->sole();
    expect($mail)->toBeInstanceOf(TleArrived::class)
        ->and($mail->page)->toBeInstanceOf(TleCollection::class)
        ->and($mail->page->members->first()->satelliteId)->toBe(25544)
        ->and($presumption->settled())->toBeTrue();
});

it('mails TleFailed on a sad conversation', function () {
    [$dock, $driver] = stargazerDock();

    stargazerClient(stargazerHttp(), $dock)->tle()->collection()->async();
    $driver->ready = [stargazerResult('stargazer.tle.collection', 'gone', status: 502)];
    $dock->pump();

    $mail = $dock->drain()->sole();
    expect($mail)->toBeInstanceOf(TleFailed::class)
        ->and($mail->ok())->toBeFalse()
        ->and($mail->reason)->toContain('502');
});
