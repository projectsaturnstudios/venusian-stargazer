<?php

use ProjectSaturnStudios\Stargazer\Exceptions\StargazerException;
use ProjectSaturnStudios\Stargazer\ImageLibrary\DataObjects\ImageAssetFile;
use ProjectSaturnStudios\Stargazer\ImageLibrary\DataObjects\ImageAssetManifest;
use ProjectSaturnStudios\Stargazer\ImageLibrary\DataObjects\ImageItemData;
use ProjectSaturnStudios\Stargazer\ImageLibrary\DataObjects\ImageLink;
use ProjectSaturnStudios\Stargazer\ImageLibrary\DataObjects\ImageLocation;
use ProjectSaturnStudios\Stargazer\ImageLibrary\DataObjects\ImageSearchItem;
use ProjectSaturnStudios\Stargazer\ImageLibrary\DataObjects\ImageSearchPage;
use ProjectSaturnStudios\Stargazer\ImageLibrary\Enums\ImageMediaType;
use Voyager\Contracts\IOPools\Promise;
use Voyager\Http\Client\Factory;
use Voyager\NutsAndBolts\Collection;

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

it('sends each Image Library async() builder on the loop', function (string $method, array $args, string $path) {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response([]));

    $promise = stargazerClient($http)->imageLibrary()->{$method}(...$args)->async();

    expect($promise)->toBeInstanceOf(Promise::class);
    $http->loop()->until(fn () => $promise->settled());
    $http->assertSent(fn ($request) => str_contains($request->url(), $path));
})->with([
    'search' => ['search', ['apollo 11',], '/search'],
    'asset' => ['asset', ['as11-40-5874',], '/asset/as11-40-5874'],
    'metadata' => ['metadata', ['as11-40-5874',], '/metadata/as11-40-5874'],
    'captions' => ['captions', ['172_ISS-Slosh',], '/captions/172_ISS-Slosh'],
]);

it('fulfils the Image Library promise with hydrated data', function () {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response(stargazerFixture('ImageLibrary', 'search')));

    $promise = stargazerClient($http)->imageLibrary()->search('apollo 11')->async();
    $result = $promise->wait();

    expect($promise->fulfilled())->toBeTrue()
        ->and($result)->toBeInstanceOf(ImageSearchPage::class)
        ->and($result->items->first()->data->first()->nasaId)->toBe('jsc2007e034221');
});

it('rejects the Image Library promise on a sad conversation', function () {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response('gone', 502));

    expect(fn () => stargazerClient($http)->imageLibrary()->search('apollo 11')->async()->wait())
        ->toThrow(StargazerException::class, '502');
});

it('refuses async() without a loop', function () {
    expect(fn () => stargazerClient(stargazerHttp(loop: false))->imageLibrary()->search('apollo 11')->async())
        ->toThrow(StargazerException::class, 'loop');
});

it('follows a location link with fetch() and fulfils with the bytes', function () {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response('SIDECARBYTES'));

    $location = ImageLocation::fromArray(stargazerFixture('ImageLibrary', 'metadata'));

    expect($location->fetch()->wait()->body())->toBe('SIDECARBYTES');
    $http->assertSent(fn ($request) => $request->url() === str_replace(' ', '%20', $location->location));
});
