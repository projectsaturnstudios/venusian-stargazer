<?php

use ProjectSaturnStudios\Stargazer\EONET\DataObjects\EonetCategory;
use ProjectSaturnStudios\Stargazer\EONET\DataObjects\EonetEvent;
use ProjectSaturnStudios\Stargazer\EONET\DataObjects\EonetEventsPage;
use ProjectSaturnStudios\Stargazer\EONET\DataObjects\EonetLayer;
use ProjectSaturnStudios\Stargazer\EONET\DataObjects\EonetMagnitude;
use ProjectSaturnStudios\Stargazer\EONET\DataObjects\EonetSource;
use ProjectSaturnStudios\Stargazer\EONET\Enums\EonetEventStatus;
use ProjectSaturnStudios\Stargazer\Exceptions\StargazerException;
use Voyager\Contracts\IOPools\Promise;
use Voyager\Http\Client\Factory;
use Voyager\NutsAndBolts\Collection;

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

it('sends each EONET async() builder on the loop', function (string $method, array $args, string $path) {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response([]));

    $promise = stargazerClient($http)->eonet()->{$method}(...$args)->async();

    expect($promise)->toBeInstanceOf(Promise::class);
    $http->loop()->until(fn () => $promise->settled());
    $http->assertSent(fn ($request) => str_contains($request->url(), $path));
})->with([
    'events' => ['events', [], '/api/v3/events'],
    'categories' => ['categories', [], '/api/v3/categories'],
    'sources' => ['sources', [], '/api/v3/sources'],
    'layers' => ['layers', [], '/api/v3/layers'],
    'magnitudes' => ['magnitudes', [], '/api/v3/magnitudes'],
]);

it('fulfils the EONET promise with hydrated data', function () {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response(stargazerFixture('EONET', 'events')));

    $promise = stargazerClient($http)->eonet()->events()->async();
    $result = $promise->wait();

    expect($promise->fulfilled())->toBeTrue()
        ->and($result)->toBeInstanceOf(EonetEventsPage::class)
        ->and($result->events->first()->id)->toBe('EONET_23453');
});

it('rejects the EONET promise on a sad conversation', function () {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response('gone', 502));

    expect(fn () => stargazerClient($http)->eonet()->events()->async()->wait())
        ->toThrow(StargazerException::class, '502');
});

it('refuses async() without a loop', function () {
    expect(fn () => stargazerClient(stargazerHttp(loop: false))->eonet()->events()->async())
        ->toThrow(StargazerException::class, 'loop');
});
