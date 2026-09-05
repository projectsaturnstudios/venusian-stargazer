<?php

use ProjectSaturnStudios\Stargazer\EONET\DataObjects\EonetCategory;
use ProjectSaturnStudios\Stargazer\EONET\DataObjects\EonetEvent;
use ProjectSaturnStudios\Stargazer\EONET\DataObjects\EonetLayer;
use ProjectSaturnStudios\Stargazer\EONET\DataObjects\EonetMagnitude;
use ProjectSaturnStudios\Stargazer\EONET\DataObjects\EonetSource;
use ProjectSaturnStudios\Stargazer\EONET\Enums\EonetEventStatus;
use ProjectSaturnStudios\Stargazer\EONET\DataObjects\EonetEventsPage;
use ProjectSaturnStudios\Stargazer\EONET\EonetArrived;
use ProjectSaturnStudios\Stargazer\EONET\EonetFailed;
use Voyager\Http\Client\Factory;
use Voyager\IOPools\Presumption;
use Voyager\NutsAndBolts\Collection;
use Voyager\NutsAndBolts\MagicAliases\Http;

beforeEach(function () {
    Http::clearResolvedInstances();
});

afterEach(function () {
    Http::clearResolvedInstances();
});

it('builds the EONET v3 events URL and hydrates the captured fixture', function () {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response(stargazerFixture('EONET', 'events')));

    $page = stargazerClient($http)->eonet()->events()->limit(1)->status(EonetEventStatus::OPEN)->get();

    expect($page->title)->toBe('EONET Events')
        ->and($page->events)->toBeInstanceOf(Collection::class)
        ->and($page->events->first())->toBeInstanceOf(EonetEvent::class)
        ->and($page->events->first()->id)->toBe('EONET_23453')
        ->and($page->events->first()->categories->first()->id)->toBe('wildfires')
        ->and($page->events->first()->geometry->first()->magnitudeValue)->toBe(10000.0);

    $http->assertSent(function ($request) {
        $url = $request->url();

        return str_contains($url, 'eonet.gsfc.nasa.gov/api/v3/events')
            && str_contains($url, 'limit=1')
            && str_contains($url, 'status=open')
            && ! str_contains($url, 'api_key=');
    });
});

it('builds the EONET categories URL and hydrates the captured fixture', function () {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response(stargazerFixture('EONET', 'categories')));

    $page = stargazerClient($http)->eonet()->categories()->get();

    expect($page->categories->first())->toBeInstanceOf(EonetCategory::class)
        ->and($page->categories->first()->id)->toBe('drought')
        ->and($page->categories)->toHaveCount(3);
});

it('carries fluent query params through async() dispatch', function () {
    [$dock, $driver] = stargazerDock();

    $presumption = stargazerClient(stargazerHttp(), $dock)
        ->eonet()
        ->categories()
        ->source('InciWeb')
        ->status('open')
        ->async();

    expect($presumption->name)->toBe('stargazer.eonet.categories')
        ->and($driver->dispatched[0]['url'])->toContain('/api/v3/categories')
        ->and($driver->dispatched[0]['url'])->toContain('source=InciWeb')
        ->and($driver->dispatched[0]['url'])->toContain('status=open')
        ->and($driver->dispatched[0]['url'])->not->toContain('api_key=');
});

it('builds the EONET sources URL and hydrates the captured fixture', function () {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response(stargazerFixture('EONET', 'sources')));

    $page = stargazerClient($http)->eonet()->sources()->get();

    expect($page->sources->first())->toBeInstanceOf(EonetSource::class)
        ->and($page->sources->first()->id)->toBe('AVO');
});

it('builds the EONET layers URL and hydrates the captured fixture', function () {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response(stargazerFixture('EONET', 'layers')));

    $page = stargazerClient($http)->eonet()->layers('wildfires')->get();

    expect($page->categories->first()->layers->first())->toBeInstanceOf(EonetLayer::class)
        ->and($page->categories->first()->layers->first()->name)->toBe('AIRS_CO_Total_Column_Day')
        ->and($page->categories->first()->layers->first()->serviceTypeId)->toBe('WMTS_1_0_0');

    $http->assertSent(fn ($request) => str_contains($request->url(), '/api/v3/layers/wildfires'));
});

it('builds the EONET magnitudes URL and hydrates the captured fixture', function () {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response(stargazerFixture('EONET', 'magnitudes')));

    $page = stargazerClient($http)->eonet()->magnitudes()->get();

    expect($page->magnitudes->first())->toBeInstanceOf(EonetMagnitude::class)
        ->and($page->magnitudes->first()->id)->toBe('ac')
        ->and($page->magnitudes->first()->unit)->toBe('acres');
});

it('dispatches each EONET async() builder under its namespaced call name', function (string $method, array $args, string $path) {
    [$dock, $driver] = stargazerDock();

    $presumption = stargazerClient(stargazerHttp(), $dock)->eonet()->{$method}(...$args)->async();

    expect($presumption)->toBeInstanceOf(Presumption::class)
        ->and($presumption->name)->toBe('stargazer.eonet.'.$method)
        ->and($driver->dispatched[0]['url'])->toContain($path);
})->with([
    'events' => ['events', [], '/api/v3/events'],
    'categories' => ['categories', [], '/api/v3/categories'],
    'sources' => ['sources', [], '/api/v3/sources'],
    'layers' => ['layers', [], '/api/v3/layers'],
    'magnitudes' => ['magnitudes', [], '/api/v3/magnitudes'],
]);

it('mails EonetArrived carrying the hydrated page through the dock', function () {
    [$dock, $driver] = stargazerDock();

    $presumption = stargazerClient(stargazerHttp(), $dock)->eonet()->events()->async();

    $driver->ready = [stargazerResult('stargazer.eonet.events', stargazerFixture('EONET', 'events'))];
    $dock->pump();

    $mail = $dock->drain()->sole();
    expect($mail)->toBeInstanceOf(EonetArrived::class)
        ->and($mail->page)->toBeInstanceOf(EonetEventsPage::class)
        ->and($mail->page->events->first()->id)->toBe('EONET_23453')
        ->and($presumption->settled())->toBeTrue();
});

it('mails EonetFailed on a sad conversation', function () {
    [$dock, $driver] = stargazerDock();

    stargazerClient(stargazerHttp(), $dock)->eonet()->events()->async();
    $driver->ready = [stargazerResult('stargazer.eonet.events', 'gone', status: 502)];
    $dock->pump();

    $mail = $dock->drain()->sole();
    expect($mail)->toBeInstanceOf(EonetFailed::class)
        ->and($mail->ok())->toBeFalse()
        ->and($mail->reason)->toContain('502');
});
