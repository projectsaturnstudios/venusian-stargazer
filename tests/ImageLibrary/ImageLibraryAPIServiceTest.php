<?php

use ProjectSaturnStudios\Stargazer\ImageLibrary\DataObjects\ImageAssetFile;
use ProjectSaturnStudios\Stargazer\ImageLibrary\DataObjects\ImageAssetManifest;
use ProjectSaturnStudios\Stargazer\ImageLibrary\DataObjects\ImageItemData;
use ProjectSaturnStudios\Stargazer\ImageLibrary\DataObjects\ImageLink;
use ProjectSaturnStudios\Stargazer\ImageLibrary\DataObjects\ImageLocation;
use ProjectSaturnStudios\Stargazer\ImageLibrary\DataObjects\ImageSearchItem;
use ProjectSaturnStudios\Stargazer\ImageLibrary\DataObjects\ImageSearchPage;
use ProjectSaturnStudios\Stargazer\ImageLibrary\Enums\ImageMediaType;
use ProjectSaturnStudios\Stargazer\ImageLibrary\ImageLibraryArrived;
use ProjectSaturnStudios\Stargazer\ImageLibrary\ImageLibraryFailed;
use ProjectSaturnStudios\Stargazer\ImageLibrary\ImageSidecarReady;
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

it('searches the Image Library and hydrates the captured fixture', function () {
    $payload = stargazerFixture('ImageLibrary', 'search');
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response($payload));

    $page = stargazerClient($http)
        ->imageLibrary()
        ->search('apollo 11')
        ->media_type(ImageMediaType::IMAGE)
        ->page_size(1)
        ->get();

    $item = $payload['collection']['items'][0];
    $data = $item['data'][0];
    $firstLink = $item['links'][0];
    $next = $payload['collection']['links'][0];

    expect($page)->toBeInstanceOf(ImageSearchPage::class)
        ->and($page->version)->toBe($payload['collection']['version'])
        ->and($page->href)->toBe($payload['collection']['href'])
        ->and($page->totalHits)->toBe($payload['collection']['metadata']['total_hits'])
        ->and($page->items)->toBeInstanceOf(Collection::class)
        ->and($page->items)->toHaveCount(1)
        ->and($page->items->first())->toBeInstanceOf(ImageSearchItem::class)
        ->and($page->items->first()->href)->toBe($item['href'])
        ->and($page->items->first()->data->first())->toBeInstanceOf(ImageItemData::class)
        ->and($page->items->first()->data->first()->nasaId)->toBe($data['nasa_id'])
        ->and($page->items->first()->data->first()->title)->toBe($data['title'])
        ->and($page->items->first()->data->first()->description)->toBe($data['description'])
        ->and($page->items->first()->data->first()->center)->toBe($data['center'])
        ->and($page->items->first()->data->first()->dateCreated)->toBe($data['date_created'])
        ->and($page->items->first()->data->first()->mediaType)->toBe(ImageMediaType::IMAGE)
        ->and($page->items->first()->data->first()->keywords->all())->toBe($data['keywords'])
        ->and($page->items->first()->data->first()->album->all())->toBe($data['album'])
        ->and($page->items->first()->links->first())->toBeInstanceOf(ImageLink::class)
        ->and($page->items->first()->links->first()->href)->toBe($firstLink['href'])
        ->and($page->items->first()->links->first()->rel)->toBe($firstLink['rel'])
        ->and($page->items->first()->links->first()->render)->toBe($firstLink['render'])
        ->and($page->items->first()->links->first()->width)->toBe($firstLink['width'])
        ->and($page->items->first()->links->first()->height)->toBe($firstLink['height'])
        ->and($page->items->first()->links->first()->size)->toBe($firstLink['size'])
        ->and($page->links->first()->rel)->toBe($next['rel'])
        ->and($page->links->first()->prompt)->toBe($next['prompt'])
        ->and($page->links->first()->href)->toBe($next['href']);

    $http->assertSent(function ($request) {
        $url = $request->url();
        $query = parse_url($url, PHP_URL_QUERY) ?? '';
        parse_str($query, $params);

        return str_contains($url, '/search')
            && ($params['q'] ?? null) === 'apollo 11'
            && ($params['media_type'] ?? null) === 'image'
            && (string) ($params['page_size'] ?? '') === '1'
            && ! array_key_exists('api_key', $params);
    });
});

it('retrieves an Image Library asset manifest from the captured fixture', function () {
    $payload = stargazerFixture('ImageLibrary', 'asset');
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response($payload));

    $manifest = stargazerClient($http)->imageLibrary()->asset('as11-40-5874')->get();

    expect($manifest)->toBeInstanceOf(ImageAssetManifest::class)
        ->and($manifest->version)->toBe($payload['collection']['version'])
        ->and($manifest->href)->toBe($payload['collection']['href'])
        ->and($manifest->items)->toHaveCount(count($payload['collection']['items']))
        ->and($manifest->items->first())->toBeInstanceOf(ImageAssetFile::class)
        ->and($manifest->items->first()->href)->toBe($payload['collection']['items'][0]['href'])
        ->and($manifest->items->last()->href)->toBe($payload['collection']['items'][5]['href']);

    $http->assertSent(function ($request) {
        $url = $request->url();

        return str_contains($url, '/asset/as11-40-5874')
            && ! str_contains($url, 'api_key=');
    });
});

it('retrieves an Image Library metadata location from the captured fixture', function () {
    $payload = stargazerFixture('ImageLibrary', 'metadata');
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response($payload));

    $location = stargazerClient($http)->imageLibrary()->metadata('as11-40-5874')->get();

    expect($location)->toBeInstanceOf(ImageLocation::class)
        ->and($location->location)->toBe($payload['location']);

    $http->assertSent(function ($request) {
        $url = $request->url();

        return str_contains($url, '/metadata/as11-40-5874')
            && ! str_contains($url, 'api_key=');
    });
});

it('retrieves an Image Library captions location from the captured fixture', function () {
    $payload = stargazerFixture('ImageLibrary', 'captions');
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response($payload));

    $location = stargazerClient($http)->imageLibrary()->captions('172_ISS-Slosh')->get();

    expect($location)->toBeInstanceOf(ImageLocation::class)
        ->and($location->location)->toBe($payload['location']);

    $http->assertSent(function ($request) {
        $url = $request->url();

        return str_contains($url, '/captions/172_ISS-Slosh')
            && ! str_contains($url, 'api_key=');
    });
});

it('dispatches each Image Library async() builder under its namespaced call name', function (string $method, array $args, string $path, ?string $query = null) {
    [$dock, $driver] = stargazerDock();

    $presumption = stargazerClient(stargazerHttp(), $dock)->imageLibrary()->{$method}(...$args)->async();

    expect($presumption)->toBeInstanceOf(Presumption::class)
        ->and($presumption->name)->toBe('stargazer.imagelibrary.'.$method)
        ->and($driver->dispatched[0]['url'])->toContain($path)
        ->and($driver->dispatched[0]['url'])->not->toContain('api_key=');

    if (! is_null($query)) {
        expect($driver->dispatched[0]['url'])->toContain($query);
    }
})->with([
    'search' => ['search', ['apollo 11'], '/search', 'q=apollo+11'],
    'asset' => ['asset', ['as11-40-5874'], '/asset/as11-40-5874'],
    'metadata' => ['metadata', ['as11-40-5874'], '/metadata/as11-40-5874'],
    'captions' => ['captions', ['172_ISS-Slosh'], '/captions/172_ISS-Slosh'],
]);

it('mails ImageLibraryArrived carrying the hydrated page through the dock', function () {
    [$dock, $driver] = stargazerDock();

    $presumption = stargazerClient(stargazerHttp(), $dock)->imageLibrary()->search('apollo 11')->async();

    $driver->ready = [stargazerResult('stargazer.imagelibrary.search', stargazerFixture('ImageLibrary', 'search'))];
    $dock->pump();

    $mail = $dock->drain()->sole();
    expect($mail)->toBeInstanceOf(ImageLibraryArrived::class)
        ->and($mail->page)->toBeInstanceOf(ImageSearchPage::class)
        ->and($mail->page->items->first()->data->first()->nasaId)->toBe('jsc2007e034221')
        ->and($presumption->settled())->toBeTrue();
});

it('mails ImageLibraryFailed on a sad conversation', function () {
    [$dock, $driver] = stargazerDock();

    stargazerClient(stargazerHttp(), $dock)->imageLibrary()->search('apollo 11')->async();
    $driver->ready = [stargazerResult('stargazer.imagelibrary.search', 'gone', status: 502)];
    $dock->pump();

    $mail = $dock->drain()->sole();
    expect($mail)->toBeInstanceOf(ImageLibraryFailed::class)
        ->and($mail->ok())->toBeFalse()
        ->and($mail->reason)->toContain('502');
});

it('follows a location link with fetchAsync and mails ImageSidecarReady', function () {
    [$dock, $driver] = stargazerDock();

    $location = ImageLocation::fromArray(stargazerFixture('ImageLibrary', 'metadata'));
    $presumption = $location->fetchAsync();

    $name = 'stargazer.imagelibrary.sidecar.'.crc32($location->location);
    expect($presumption->name)->toBe($name)
        ->and($driver->dispatched[0]['url'])->toBe($location->location)
        ->and($location->fetchAsync())->toBe($presumption);

    $driver->ready = [stargazerResult($name, 'SIDECARBYTES')];
    $dock->pump();

    $mail = $dock->drain()->sole();
    expect($mail)->toBeInstanceOf(ImageSidecarReady::class)
        ->and($mail->location)->toBe($location)
        ->and($mail->result->body)->toBe('SIDECARBYTES');
});
