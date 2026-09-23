<?php

use ProjectSaturnStudios\Stargazer\APOD\DataObjects\AstronomyPicture;
use ProjectSaturnStudios\Stargazer\Exceptions\StargazerException;
use ProjectSaturnStudios\Stargazer\NasaClient;
use Voyager\Contracts\IOPools\Promise;
use Voyager\Http\Client\Factory;
use Voyager\NutsAndBolts\Collection;
use Voyager\NutsAndBolts\DataObjects\Carbon;

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

it('sends each APOD async() builder on the loop', function (string $method, array $args, string $path) {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response([]));

    $promise = stargazerClient($http)->apod()->{$method}(...$args)->async();

    expect($promise)->toBeInstanceOf(Promise::class);
    $http->loop()->until(fn () => $promise->settled());
    $http->assertSent(fn ($request) => str_contains($request->url(), $path));
})->with([
    'date' => ['date', ['2015-06-03',], '/planetary/apod'],
    'range' => ['range', ['2015-06-03', '2015-06-04',], '/planetary/apod'],
    'count' => ['count', [2,], '/planetary/apod'],
]);

it('fulfils the APOD promise with hydrated data', function () {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response(stargazerFixture('APOD', 'date')));

    $promise = stargazerClient($http)->apod()->date('2015-06-03')->async();
    $result = $promise->wait();

    expect($promise->fulfilled())->toBeTrue()
        ->and($result)->toBeInstanceOf(AstronomyPicture::class)
        ->and($result->title)->toBe('Hyperion: Sponge Moon of Saturn');
});

it('rejects the APOD promise on a sad conversation', function () {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response('gone', 500));

    expect(fn () => stargazerClient($http)->apod()->date('2015-06-03')->async()->wait())
        ->toThrow(StargazerException::class, '500');
});

it('refuses async() without a loop', function () {
    expect(fn () => stargazerClient(stargazerHttp(loop: false))->apod()->date('2015-06-03')->async())
        ->toThrow(StargazerException::class, 'loop');
});

it('follows a picture link with render() and skips embed days', function () {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response('JPGBYTES'));

    $picture = AstronomyPicture::fromArray(stargazerFixture('APOD', 'date'));

    expect($picture->render()->wait()->body())->toBe('JPGBYTES');
    $http->assertSent(fn ($request) => $request->url() === $picture->url);

    $embed = AstronomyPicture::fromArray(['date' => '2020-01-01', 'title' => 'x', 'media_type' => 'video', 'url' => 'https://www.youtube.com/embed/abc']);
    expect($embed->render())->toBeNull();
});
