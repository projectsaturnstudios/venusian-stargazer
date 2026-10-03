<?php

use ProjectSaturnStudios\Stargazer\APOD\DataObjects\AstronomyPicture;
use ProjectSaturnStudios\Stargazer\Exceptions\StargazerException;
use ProjectSaturnStudios\Stargazer\NasaClient;
use Voyager\Contracts\IOPools\Promise;
use Voyager\Http\Client\Factory;
use Voyager\NutsAndBolts\Collection;
use Voyager\NutsAndBolts\DataObjects\Carbon;

it('addresses a single APOD day as YYMMDD and hydrates the captured fixture', function () {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response(stargazerFixture('APOD', 'date')));

    $picture = stargazerClient($http)->apod()->date('2015-06-03')->get();

    expect($picture)->toBeInstanceOf(AstronomyPicture::class)
        ->and($picture->date)->toBe('2015-06-03')
        ->and($picture->title)->toBe("Flyby Image of Saturn's Sponge Moon Hyperion")
        ->and($picture->media_type)->toBe('image')
        ->and($picture->copyright)->toBe('NASA, JPL-Caltech, SSI')
        ->and($picture->permalink)->toBe('https://science.nasa.gov/image-article/apod-2015-june-3-flyby-image-of-saturns-sponge-moon-hyperion/')
        ->and($picture->url)->toBe($picture->hdurl)
        ->and($picture->thumbnail_url)->toBeNull();

    $http->assertSent(fn ($request) => $request->url() === 'https://science.nasa.gov/wp-json/wp/v2/apod-basic/150603');
});

it('sends no api_key to science.nasa.gov', function () {
    $pending = (new NasaClient(api_key: 'TEST_KEY'))->apod()->date('2015-06-03');

    expect($pending->query())->not->toHaveKey('api_key');
});

it('gives the explanation as plain text without its lead-in, link spacing or site footer', function () {
    $hyperion = AstronomyPicture::fromArray(stargazerFixture('APOD', 'date'));
    $sharpless = AstronomyPicture::fromArray(stargazerFixture('APOD', 'large'));

    expect($hyperion->explanation)->toStartWith('Why does this moon look like a sponge? To better investigate, NASA and ESA sent')
        ->and($hyperion->explanation)->toContain("past Saturn's moon Hyperion, once again, earlier this week")
        ->and($hyperion->explanation)->toContain('featured above, raw and unprocessed')
        ->and($hyperion->explanation)->not->toContain('<')
        ->and($sharpless->explanation)->toEndWith('Which object is your favorite?')
        ->and($sharpless->explanation)->not->toContain('Tomorrow')
        ->and($sharpless->explanation)->not->toContain('APOD Submissions');
});

it('defaults a missing APOD date to today in the current timezone', function () {
    $previous_timezone = date_default_timezone_get();
    date_default_timezone_set('America/New_York');
    Carbon::setTestNow(Carbon::parse('2026-09-04 02:00:00', 'UTC'));

    try {
        $pending = (new NasaClient(api_key: 'TEST_KEY'))->apod()->date();

        expect($pending->url())->toEndWith('/apod-basic/260903');
    } finally {
        Carbon::setTestNow();
        date_default_timezone_set($previous_timezone);
    }
});

it('asks for a date range as one page and hydrates it oldest first', function () {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response(stargazerFixture('APOD', 'range')));

    $pictures = stargazerClient($http)->apod()->range('2015-06-03', '2015-06-04')->get();

    expect($pictures)->toBeInstanceOf(Collection::class)
        ->and($pictures)->toHaveCount(2)
        ->and($pictures->first())->toBeInstanceOf(AstronomyPicture::class)
        ->and($pictures->first()->date)->toBe('2015-06-03')
        ->and($pictures->last()->date)->toBe('2015-06-04')
        ->and($pictures->last()->title)->toBe('NGC 2419: Intergalactic Wanderer');

    $http->assertSent(function ($request) {
        $url = $request->url();

        return str_contains($url, 'date_from=150603')
            && str_contains($url, 'date_to=150604')
            && str_contains($url, 'per_page=2');
    });
});

it('refuses a range longer than one page', function () {
    expect(fn () => (new NasaClient(api_key: 'TEST_KEY'))->apod()->range('2015-01-01', '2015-04-11'))
        ->toThrow(InvalidArgumentException::class, 'at most 100 days, got 101');
});

it('asks for count consecutive days from a page inside the archive and hydrates them oldest first', function () {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response(stargazerFixture('APOD', 'count')));

    $pictures = stargazerClient($http)->apod()->count(2)->get();

    expect($pictures)->toBeInstanceOf(Collection::class)
        ->and($pictures)->toHaveCount(2)
        ->and($pictures->first()->date)->toBe('2015-10-18')
        ->and($pictures->last()->title)->toBe('The Southern Cross in a Southern Sky');

    $days = (int) (new DateTimeImmutable('1995-06-16'))->diff(new DateTimeImmutable('today'))->days + 1;

    $http->assertSent(function ($request) use ($days) {
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

        return $query['per_page'] === '2'
            && (int) $query['page'] >= 1
            && (int) $query['page'] <= intdiv($days, 2);
    });
});

it('refuses a count outside one page', function (int $count) {
    expect(fn () => (new NasaClient(api_key: 'TEST_KEY'))->apod()->count($count))
        ->toThrow(InvalidArgumentException::class, "1 to 100, got {$count}");
})->with([0, 101]);

it('plays a video day from its mp4 and keeps the still as the thumbnail', function () {
    $picture = AstronomyPicture::fromArray(stargazerFixture('APOD', 'video'));

    expect($picture->media_type)->toBe('video')
        ->and($picture->url)->toBe('https://assets.science.nasa.gov/content/dam/science/cds/apod/apod/2026/september/NoctilucentNeowise_Girotti.mp4')
        ->and($picture->thumbnail_url)->toBe($picture->hdurl)
        ->and($picture->mediaKind())->toBe('video')
        ->and($picture->copyright)->toBe('Paolo Girotti');
});

it('asks the resizing service for a screen-sized picture and keeps the full size as hdurl', function () {
    $picture = AstronomyPicture::fromArray(stargazerFixture('APOD', 'large'));

    expect($picture->hdurl)->toBe('https://assets.science.nasa.gov/dynamicimage/assets/science/cds/apod/apod/2026/october/sharpless_catalog.png?w=4455&h=5592&fit=clip&crop=faces%2Cfocalpoint')
        ->and($picture->url)->toBe('https://assets.science.nasa.gov/dynamicimage/assets/science/cds/apod/apod/2026/october/sharpless_catalog.png?w=1275&h=1600&fit=clip&crop=faces%2Cfocalpoint');
});

it('treats a player iframe as an embed day with nothing to fetch', function () {
    $embed = AstronomyPicture::fromArray([
        'date' => '2020-01-01',
        'title' => 'x',
        'media_type' => 'video',
        'hdurl' => 'https://example.test/still.jpg',
        'basic_html' => '<iframe width="960" height="540" src="https://www.youtube.com/embed/abc?rel=0&amp;autoplay=1"></iframe>',
    ]);

    expect($embed->url)->toBe('https://www.youtube.com/embed/abc?rel=0&autoplay=1')
        ->and($embed->mediaKind())->toBeNull()
        ->and($embed->render())->toBeNull();
});

it('sends each APOD async() builder on the loop', function (string $method, array $args, string $path) {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response([]));

    $promise = stargazerClient($http)->apod()->{$method}(...$args)->async();

    expect($promise)->toBeInstanceOf(Promise::class);
    $http->loop()->until(fn () => $promise->settled());
    $http->assertSent(fn ($request) => str_contains($request->url(), $path));
})->with([
    'date' => ['date', ['2015-06-03'], '/apod-basic/150603'],
    'range' => ['range', ['2015-06-03', '2015-06-04'], '/apod-basic?'],
    'count' => ['count', [2], '/apod-basic?'],
]);

it('fulfils the APOD promise with hydrated data', function () {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response(stargazerFixture('APOD', 'date')));

    $promise = stargazerClient($http)->apod()->date('2015-06-03')->async();
    $result = $promise->wait();

    expect($promise->fulfilled())->toBeTrue()
        ->and($result)->toBeInstanceOf(AstronomyPicture::class)
        ->and($result->title)->toBe("Flyby Image of Saturn's Sponge Moon Hyperion");
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

it('follows a picture link with render(), the full size with render(hd: true)', function () {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response('PNGBYTES'));

    $picture = AstronomyPicture::fromArray(stargazerFixture('APOD', 'large'));

    expect($picture->render()->wait()->body())->toBe('PNGBYTES')
        ->and($picture->render(hd: true)->wait()->body())->toBe('PNGBYTES');

    $http->assertSent(fn ($request) => $request->url() === $picture->url);
    $http->assertSent(fn ($request) => $request->url() === $picture->hdurl);
});
