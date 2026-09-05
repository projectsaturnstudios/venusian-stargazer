<?php

use ProjectSaturnStudios\Stargazer\APOD\DataObjects\AstronomyPicture;
use ProjectSaturnStudios\Stargazer\NasaClient;
use Voyager\Http\Client\Factory;
use ProjectSaturnStudios\Stargazer\APOD\APODArrived;
use ProjectSaturnStudios\Stargazer\APOD\APODFailed;
use Voyager\IOPools\Presumption;
use Voyager\NutsAndBolts\Collection;
use Voyager\NutsAndBolts\DataObjects\Carbon;
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

it('defaults a missing APOD date to today in the current timezone', function () {
    $previous_timezone = date_default_timezone_get();
    date_default_timezone_set('America/New_York');
    Carbon::setTestNow(Carbon::parse('2026-09-04 02:00:00', 'UTC'));

    try {
        $pending = (new NasaClient(api_key: 'TEST_KEY'))->apod()->date();

        expect($pending->query()['date'])->toBe('2026-09-03');
    } finally {
        Carbon::setTestNow();
        date_default_timezone_set($previous_timezone);
    }
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

it('dispatches each APOD async() builder under its namespaced call name', function (string $method, array $args) {
    [$dock, $driver] = stargazerDock();

    $presumption = stargazerClient(stargazerHttp(), $dock)->apod()->{$method}(...$args)->async();

    expect($presumption)->toBeInstanceOf(Presumption::class)
        ->and($presumption->name)->toBe('stargazer.apod.'.$method)
        ->and($driver->dispatched[0]['url'])->toContain('/planetary/apod');
})->with([
    'date' => ['date', ['2015-06-03']],
    'range' => ['range', ['2015-06-03', '2015-06-04']],
    'count' => ['count', [2]],
]);

it('mails APODArrived for a single-object date payload and a list payload alike', function () {
    [$dock, $driver] = stargazerDock();
    $client = stargazerClient(stargazerHttp(), $dock);

    $client->apod()->date('2015-06-03')->async();
    $driver->ready = [stargazerResult('stargazer.apod.date', stargazerFixture('APOD', 'date'))];
    $dock->pump();

    $single = $dock->drain()->sole();
    expect($single)->toBeInstanceOf(APODArrived::class)
        ->and($single->apods)->toHaveCount(1);

    $client->apod()->count(2)->async();
    $driver->ready = [stargazerResult('stargazer.apod.count', stargazerFixture('APOD', 'count'))];
    $dock->pump();

    $many = $dock->drain()->sole();
    expect($many)->toBeInstanceOf(APODArrived::class)
        ->and(count($many->apods))->toBeGreaterThan(1);
});

it('mails APODFailed on a sad conversation', function () {
    [$dock, $driver] = stargazerDock();

    stargazerClient(stargazerHttp(), $dock)->apod()->date('2015-06-03')->async();
    $driver->ready = [stargazerResult('stargazer.apod.date', '{"code":500}', status: 500)];
    $dock->pump();

    $mail = $dock->drain()->sole();
    expect($mail)->toBeInstanceOf(APODFailed::class)
        ->and($mail->ok())->toBeFalse()
        ->and($mail->reason)->toContain('500');
});
