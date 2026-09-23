<?php

use ProjectSaturnStudios\Stargazer\Exceptions\StargazerException;
use ProjectSaturnStudios\Stargazer\NasaClient;
use ProjectSaturnStudios\Stargazer\TLE\DataObjects\TleCollection;
use ProjectSaturnStudios\Stargazer\TLE\DataObjects\TleRecord;
use Voyager\Contracts\IOPools\Promise;
use Voyager\Http\Client\Factory;

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
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response(tleFixture($file)));

    return $http;
}

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

it('sends each TLE async() builder on the loop', function (string $method, array $args, string $path) {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response([]));

    $promise = stargazerClient($http)->tle()->{$method}(...$args)->async();

    expect($promise)->toBeInstanceOf(Promise::class);
    $http->loop()->until(fn () => $promise->settled());
    $http->assertSent(fn ($request) => str_contains($request->url(), $path));
})->with([
    'collection' => ['collection', [], '/api/tle'],
    'search' => ['search', ['ISS',], 'search=ISS'],
    'satellite' => ['satellite', [25544,], '/api/tle/25544'],
]);

it('fulfils the TLE promise with hydrated data', function () {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response(stargazerFixture('TLE', 'collection')));

    $promise = stargazerClient($http)->tle()->collection()->async();
    $result = $promise->wait();

    expect($promise->fulfilled())->toBeTrue()
        ->and($result)->toBeInstanceOf(TleCollection::class)
        ->and($result->members->first()->satelliteId)->toBe(25544);
});

it('rejects the TLE promise on a sad conversation', function () {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response('gone', 502));

    expect(fn () => stargazerClient($http)->tle()->collection()->async()->wait())
        ->toThrow(StargazerException::class, '502');
});

it('refuses async() without a loop', function () {
    expect(fn () => stargazerClient(stargazerHttp(loop: false))->tle()->collection()->async())
        ->toThrow(StargazerException::class, 'loop');
});
