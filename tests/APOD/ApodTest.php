<?php

use ProjectSaturnStudios\Stargazer\APOD\DataObjects\AstronomyPicture;
use Voyager\Http\Client\Factory;
use Voyager\IOPools\PendingCall;
use Voyager\NutsAndBolts\Collection;
use Voyager\NutsAndBolts\MagicAliases\Http;

beforeEach(function () {
    Http::clearResolvedInstances();
});

afterEach(function () {
    Http::clearResolvedInstances();
});

it('builds a single-date APOD request and hydrates the captured fixture', function () {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response(stargazerFixture('APOD', 'date')));

    $picture = stargazerClient($http)->apod()->date('2015-06-03')->get();

    expect($picture)->toBeInstanceOf(AstronomyPicture::class)
        ->and($picture->date)->toBe('2015-06-03')
        ->and($picture->title)->toBe('Hyperion: Sponge Moon of Saturn')
        ->and($picture->media_type)->toBe('image')
        ->and($picture->copyright)->toBe('NASA/JPL/Space Science Institute');

    $http->assertSent(function ($request) {
        $url = $request->url();

        return str_contains($url, '/planetary/apod')
            && str_contains($url, 'date=2015-06-03')
            && str_contains($url, 'api_key=TEST_KEY');
    });
});

it('builds an APOD date-range request and hydrates a Collection of pictures', function () {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response(stargazerFixture('APOD', 'range')));

    $pictures = stargazerClient($http)->apod()->range('2015-06-03', '2015-06-04')->get();

    expect($pictures)->toBeInstanceOf(Collection::class)
        ->and($pictures)->toHaveCount(2)
        ->and($pictures->first())->toBeInstanceOf(AstronomyPicture::class)
        ->and($pictures->first()->date)->toBe('2015-06-03')
        ->and($pictures->last()->date)->toBe('2015-06-04');

    $http->assertSent(function ($request) {
        $url = $request->url();

        return str_contains($url, 'start_date=2015-06-03')
            && str_contains($url, 'end_date=2015-06-04');
    });
});

it('builds an APOD count request and hydrates a Collection of pictures', function () {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response(stargazerFixture('APOD', 'count')));

    $pictures = stargazerClient($http)->apod()->count(2, true)->get();

    expect($pictures)->toBeInstanceOf(Collection::class)
        ->and($pictures)->toHaveCount(2)
        ->and($pictures->last()->title)->toBe('Filaments of the Vela Supernova Remnant')
        ->and($pictures->last()->thumbnail_url)->toBe('https://img.youtube.com/vi/example/0.jpg');

    $http->assertSent(function ($request) {
        $url = $request->url();

        return str_contains($url, 'count=2')
            && str_contains($url, 'thumbs=true');
    });
});

it('returns a namespaced PendingCall from each APOD async() builder', function (string $method, array $args) {
    $http = stargazerHttp();
    [$driver, $pool] = stargazerPool();

    $call = stargazerClient($http, $pool)->apod()->{$method}(...$args)->async();

    expect($call)->toBeInstanceOf(PendingCall::class)
        ->and($call->name)->toBe('stargazer.apod.'.$method)
        ->and($driver->dispatched[0]['url'])->toContain('/planetary/apod');
})->with([
    'date' => ['date', ['2015-06-03']],
    'range' => ['range', ['2015-06-03', '2015-06-04']],
    'count' => ['count', [2]],
]);
