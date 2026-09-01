<?php

use ProjectSaturnStudios\Stargazer\NeoWs\DataObjects\NearEarthObject;
use ProjectSaturnStudios\Stargazer\NeoWs\DataObjects\NeoBrowse;
use ProjectSaturnStudios\Stargazer\NeoWs\DataObjects\NeoFeed;
use Voyager\Http\Client\Factory;
use Voyager\IOPools\PendingCall;
use Voyager\NutsAndBolts\MagicAliases\Http;

beforeEach(function () {
    Http::clearResolvedInstances();
});

afterEach(function () {
    Http::clearResolvedInstances();
});

it('builds the NeoWs feed URL and hydrates the captured fixture', function () {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response(stargazerFixture('NeoWs', 'feed')));

    $feed = stargazerClient($http)->neows()->feed('2015-09-07', '2015-09-08')->get();

    expect($feed)->toBeInstanceOf(NeoFeed::class)
        ->and($feed->element_count)->toBe(2)
        ->and($feed->near_earth_objects)->toHaveCount(2)
        ->and($feed->near_earth_objects->get('2015-09-08')->first())->toBeInstanceOf(NearEarthObject::class)
        ->and($feed->near_earth_objects->get('2015-09-08')->first()->id)->toBe('2465633')
        ->and($feed->near_earth_objects->get('2015-09-08')->first()->is_potentially_hazardous_asteroid)->toBeTrue()
        ->and($feed->near_earth_objects->get('2015-09-08')->first()->close_approach_data->first()->orbiting_body)->toBe('Earth');

    $http->assertSent(function ($request) {
        $url = $request->url();

        return str_contains($url, '/neo/rest/v1/feed')
            && str_contains($url, 'start_date=2015-09-07')
            && str_contains($url, 'end_date=2015-09-08')
            && str_contains($url, 'api_key=TEST_KEY');
    });
});

it('builds the NeoWs lookup URL and hydrates orbital data from the fixture', function () {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response(stargazerFixture('NeoWs', 'lookup')));

    $neo = stargazerClient($http)->neows()->lookup('3542519')->get();

    expect($neo)->toBeInstanceOf(NearEarthObject::class)
        ->and($neo->id)->toBe('3542519')
        ->and($neo->name)->toBe('(2010 PK9)')
        ->and($neo->orbital_data->orbit_class->orbit_class_type)->toBe('APO')
        ->and($neo->estimated_diameter->kilometers->estimated_diameter_min)->toBe(0.116);

    $http->assertSent(fn ($request) => str_contains($request->url(), '/neo/rest/v1/neo/3542519'));
});

it('builds the NeoWs browse URL and hydrates page metadata from the fixture', function () {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response(stargazerFixture('NeoWs', 'browse')));

    $browse = stargazerClient($http)->neows()->browse(0, 1)->get();

    expect($browse)->toBeInstanceOf(NeoBrowse::class)
        ->and($browse->page->number)->toBe(0)
        ->and($browse->page->size)->toBe(1)
        ->and($browse->near_earth_objects->first()->name)->toBe('433 Eros (A898 PA)');

    $http->assertSent(function ($request) {
        $url = $request->url();

        return str_contains($url, '/neo/rest/v1/neo/browse')
            && str_contains($url, 'page=0')
            && str_contains($url, 'size=1');
    });
});

it('returns a namespaced PendingCall from each NeoWs async() builder', function (string $method, array $args, string $path) {
    $http = stargazerHttp();
    [$driver, $pool] = stargazerPool();

    $call = stargazerClient($http, $pool)->neows()->{$method}(...$args)->async();

    expect($call)->toBeInstanceOf(PendingCall::class)
        ->and($call->name)->toBe('stargazer.neows.'.$method)
        ->and($driver->dispatched[0]['url'])->toContain($path);
})->with([
    'feed' => ['feed', ['2015-09-07', '2015-09-08'], '/neo/rest/v1/feed'],
    'lookup' => ['lookup', ['3542519'], '/neo/rest/v1/neo/3542519'],
    'browse' => ['browse', [0, 1], '/neo/rest/v1/neo/browse'],
]);
