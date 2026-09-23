<?php

use ProjectSaturnStudios\Stargazer\EPIC\DataObjects\EpicAvailableDate;
use ProjectSaturnStudios\Stargazer\EPIC\DataObjects\EpicImage;
use ProjectSaturnStudios\Stargazer\EPIC\Enums\EpicCollection;
use ProjectSaturnStudios\Stargazer\EPIC\Enums\EpicImageType;
use ProjectSaturnStudios\Stargazer\Exceptions\StargazerException;
use ProjectSaturnStudios\Stargazer\NasaClient;
use Voyager\Contracts\IOPools\Promise;
use Voyager\Http\Client\Factory;
use Voyager\NutsAndBolts\Collection;

function epicFixture(string $file): array
{
    return json_decode(
        file_get_contents(dirname(__DIR__).'/Fixtures/EPIC/'.$file),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );
}

function epicHttp(string $file): Factory
{
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response(epicFixture($file)));

    return $http;
}

it('builds the EPIC natural metadata URL for the most recent imagery', function () {
    $http = epicHttp('natural.json');

    (new NasaClient(api_key: 'TEST_KEY', http: $http))->epic()->natural()->get();

    $http->assertSent(function ($request) {
        $url = $request->url();

        return str_contains($url, '/EPIC/api/natural')
            && ! str_contains($url, '/date/')
            && str_contains($url, 'api_key=TEST_KEY');
    });
});

it('builds the EPIC natural metadata URL for a given date', function () {
    $http = epicHttp('naturalDate.json');

    (new NasaClient(api_key: 'TEST_KEY', http: $http))->epic()->natural('2015-10-31')->get();

    $http->assertSent(fn ($request) => str_contains($request->url(), '/EPIC/api/natural/date/2015-10-31'));
});

it('hydrates EPIC natural imagery metadata from the captured fixture', function () {
    $payload = epicFixture('naturalDate.json');
    $http = epicHttp('naturalDate.json');

    $images = (new NasaClient(api_key: 'TEST_KEY', http: $http))->epic()->natural('2015-10-31')->get();

    expect($images)->toBeInstanceOf(Collection::class)
        ->and($images)->toHaveCount(count($payload))
        ->and($images->first())->toBeInstanceOf(EpicImage::class);

    $first = $payload[0];
    $image = $images->first();

    expect($image->identifier)->toBe($first['identifier'])
        ->and($image->caption)->toBe($first['caption'])
        ->and($image->image)->toBe($first['image'])
        ->and($image->version)->toBe($first['version'])
        ->and($image->date)->toBe($first['date'])
        ->and($image->centroid->lat)->toBe($first['centroid_coordinates']['lat'])
        ->and($image->centroid->lon)->toBe($first['centroid_coordinates']['lon'])
        ->and($image->dscovrPosition->x)->toBe($first['dscovr_j2000_position']['x'])
        ->and($image->dscovrPosition->y)->toBe($first['dscovr_j2000_position']['y'])
        ->and($image->dscovrPosition->z)->toBe($first['dscovr_j2000_position']['z'])
        ->and($image->lunarPosition->x)->toBe($first['lunar_j2000_position']['x'])
        ->and($image->sunPosition->z)->toBe($first['sun_j2000_position']['z'])
        ->and($image->attitude->q0)->toBe($first['attitude_quaternions']['q0'])
        ->and($image->attitude->q3)->toBe($first['attitude_quaternions']['q3'])
        ->and($image->coords->centroid->lat)->toBe($first['coords']['centroid_coordinates']['lat'])
        ->and($image->archiveUrl(EpicCollection::NATURAL, EpicImageType::PNG))
        ->toBe('https://epic.gsfc.nasa.gov/archive/natural/2015/10/31/png/epic_1b_20151031003633.png');
});

it('builds the EPIC enhanced metadata URL and hydrates the captured fixture', function () {
    $payload = epicFixture('enhanced.json');
    $http = epicHttp('enhanced.json');

    $images = (new NasaClient(api_key: 'TEST_KEY', http: $http))->epic()->enhanced()->get();

    expect($images)->toBeInstanceOf(Collection::class)
        ->and($images->first())->toBeInstanceOf(EpicImage::class)
        ->and($images->first()->identifier)->toBe($payload[0]['identifier'])
        ->and($images->first()->image)->toBe($payload[0]['image']);

    $http->assertSent(fn ($request) => str_contains($request->url(), '/EPIC/api/enhanced')
        && ! str_contains($request->url(), '/date/'));
});

it('builds the EPIC enhanced date URL from the captured date fixture', function () {
    $http = epicHttp('enhancedDate.json');

    (new NasaClient(api_key: 'TEST_KEY', http: $http))->epic()->enhanced('2015-10-31')->get();

    $http->assertSent(fn ($request) => str_contains($request->url(), '/EPIC/api/enhanced/date/2015-10-31'));
});

it('lists available natural dates from the captured fixture', function () {
    $payload = epicFixture('naturalAvailable.json');
    $http = epicHttp('naturalAvailable.json');

    $dates = (new NasaClient(api_key: 'TEST_KEY', http: $http))->epic()->naturalAvailable()->get();

    expect($dates)->toBeInstanceOf(Collection::class)
        ->and($dates)->toHaveCount(count($payload))
        ->and($dates->first())->toBeInstanceOf(EpicAvailableDate::class)
        ->and($dates->first()->date)->toBe($payload[0])
        ->and($dates->last()->date)->toBe($payload[array_key_last($payload)]);

    $http->assertSent(fn ($request) => str_contains($request->url(), '/EPIC/api/natural/available'));
});

it('lists available enhanced dates from the captured fixture', function () {
    $payload = epicFixture('enhancedAvailable.json');
    $http = epicHttp('enhancedAvailable.json');

    $dates = (new NasaClient(api_key: 'TEST_KEY', http: $http))->epic()->enhancedAvailable()->get();

    expect($dates->first())->toBeInstanceOf(EpicAvailableDate::class)
        ->and($dates->first()->date)->toBe($payload[0]);

    $http->assertSent(fn ($request) => str_contains($request->url(), '/EPIC/api/enhanced/available'));
});

it('sends each EPIC async() builder on the loop', function (string $method, array $args, string $path) {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response([]));

    $promise = stargazerClient($http)->epic()->{$method}(...$args)->async();

    expect($promise)->toBeInstanceOf(Promise::class);
    $http->loop()->until(fn () => $promise->settled());
    $http->assertSent(fn ($request) => str_contains($request->url(), $path));
})->with([
    'natural' => ['natural', [], '/EPIC/api/natural'],
    'enhanced' => ['enhanced', [], '/EPIC/api/enhanced'],
    'naturalAvailable' => ['naturalAvailable', [], '/EPIC/api/natural/available'],
]);

it('fulfils the EPIC promise with hydrated data', function () {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response(epicFixture('natural.json')));

    $promise = stargazerClient($http)->epic()->natural()->async();
    $result = $promise->wait();

    expect($promise->fulfilled())->toBeTrue()
        ->and($result)->toBeInstanceOf(Collection::class)
        ->and($result->first())->toBeInstanceOf(EpicImage::class);
});

it('rejects the EPIC promise on a sad conversation', function () {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response('gone', 500));

    expect(fn () => stargazerClient($http)->epic()->natural()->async()->wait())
        ->toThrow(StargazerException::class, '500');
});

it('refuses async() without a loop', function () {
    expect(fn () => stargazerClient(stargazerHttp(loop: false))->epic()->natural()->async())
        ->toThrow(StargazerException::class, 'loop');
});

it('follows an image link with render() and fulfils with the bytes', function () {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response('PNGBYTES'));

    $image = EpicImage::fromArray(epicFixture('natural.json')[0]);
    $response = $image->render(EpicCollection::NATURAL, EpicImageType::PNG)->wait();

    expect($response->body())->toBe('PNGBYTES');
    $http->assertSent(fn ($request) => $request->url() === $image->archiveUrl(EpicCollection::NATURAL, EpicImageType::PNG));
});
