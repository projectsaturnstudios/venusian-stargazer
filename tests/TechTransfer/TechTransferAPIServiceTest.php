<?php

use ProjectSaturnStudios\Stargazer\Exceptions\StargazerException;
use ProjectSaturnStudios\Stargazer\NasaClient;
use ProjectSaturnStudios\Stargazer\TechTransfer\DataObjects\TechTransferPage;
use ProjectSaturnStudios\Stargazer\TechTransfer\DataObjects\TechTransferRecord;
use Voyager\Contracts\IOPools\Promise;
use Voyager\Http\Client\Factory;

function techTransferFixture(string $file): array
{
    return json_decode(
        file_get_contents(dirname(__DIR__).'/Fixtures/TechTransfer/'.$file),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );
}

function techTransferHttp(string $file): Factory
{
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response(techTransferFixture($file)));

    return $http;
}

function expectHydratedRecord(TechTransferRecord $record, array $row): void
{
    expect($record->id)->toBe($row[0])
        ->and($record->caseNumber)->toBe($row[1])
        ->and($record->title)->toBe($row[2])
        ->and($record->description)->toBe($row[3])
        ->and($record->documentId)->toBe($row[4])
        ->and($record->category)->toBe($row[5])
        ->and($record->releaseType)->toBe($row[6])
        ->and($record->secondaryCategory)->toBe($row[7])
        ->and($record->tertiaryCategory)->toBe($row[8])
        ->and($record->center)->toBe($row[9])
        ->and($record->imageUrl)->toBe($row[10])
        ->and($record->detailUrl)->toBe($row[11])
        ->and($record->score)->toBe($row[12]);
}

it('searches TechTransfer patents and hydrates the captured fixture', function () {
    $payload = techTransferFixture('patent.json');
    $http = techTransferHttp('patent.json');

    $page = (new NasaClient(api_key: 'TEST_KEY', http: $http))->techtransfer()->patent('engine')->get();

    expect($page)->toBeInstanceOf(TechTransferPage::class)
        ->and($page->count)->toBe($payload['count'])
        ->and($page->total)->toBe($payload['total'])
        ->and($page->perPage)->toBe($payload['perpage'])
        ->and($page->page)->toBe($payload['page'])
        ->and($page->results)->toHaveCount(count($payload['results']))
        ->and($page->results->first())->toBeInstanceOf(TechTransferRecord::class);

    expectHydratedRecord($page->results->first(), $payload['results'][0]);
    expectHydratedRecord($page->results->last(), $payload['results'][1]);

    $http->assertSent(function ($request) {
        $url = $request->url();

        // technology.nasa.gov serves TechTransfer keyless — no api_key rides.
        return str_contains($url, 'technology.nasa.gov/api/api/patent/')
            && str_contains($url, 'patent=engine')
            && ! str_contains($url, 'api_key=');
    });
});

it('searches TechTransfer software and hydrates the captured fixture', function () {
    $payload = techTransferFixture('software.json');
    $http = techTransferHttp('software.json');

    $page = (new NasaClient(api_key: 'TEST_KEY', http: $http))->techtransfer()->software('guidance')->get();

    expect($page->results->first())->toBeInstanceOf(TechTransferRecord::class)
        ->and($page->total)->toBe($payload['total']);

    expectHydratedRecord($page->results->first(), $payload['results'][0]);

    $http->assertSent(function ($request) {
        $url = $request->url();

        return str_contains($url, '/api/api/software/')
            && str_contains($url, 'software=guidance');
    });
});

it('searches TechTransfer spinoffs and hydrates the captured fixture', function () {
    $payload = techTransferFixture('spinoff.json');
    $http = techTransferHttp('spinoff.json');

    $page = (new NasaClient(api_key: 'TEST_KEY', http: $http))->techtransfer()->spinoff('battery')->get();

    expectHydratedRecord($page->results->first(), $payload['results'][0]);

    $http->assertSent(function ($request) {
        $url = $request->url();

        return str_contains($url, '/api/api/spinoff/')
            && (str_contains($url, 'Spinoff=battery') || str_contains($url, 'spinoff=battery'));
    });
});

it('sends each TechTransfer async() builder on the loop', function (string $method, array $args, string $path) {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response([]));

    $promise = stargazerClient($http)->techtransfer()->{$method}(...$args)->async();

    expect($promise)->toBeInstanceOf(Promise::class);
    $http->loop()->until(fn () => $promise->settled());
    $http->assertSent(fn ($request) => str_contains($request->url(), $path));
})->with([
    'patent' => ['patent', ['engine',], '/api/api/patent/'],
    'software' => ['software', ['guidance',], '/api/api/software/'],
    'spinoff' => ['spinoff', ['battery',], '/api/api/spinoff/'],
]);

it('fulfils the TechTransfer promise with hydrated data', function () {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response(techTransferFixture('patent.json')));

    $promise = stargazerClient($http)->techtransfer()->patent('engine')->async();
    $result = $promise->wait();

    expect($promise->fulfilled())->toBeTrue()
        ->and($result)->toBeInstanceOf(TechTransferPage::class)
        ->and($result->results->first()->id)->toBe('64e71c1a64038afc1d0a01d2');
});

it('rejects the TechTransfer promise on a sad conversation', function () {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response('gone', 502));

    expect(fn () => stargazerClient($http)->techtransfer()->patent('engine')->async()->wait())
        ->toThrow(StargazerException::class, '502');
});

it('refuses async() without a loop', function () {
    expect(fn () => stargazerClient(stargazerHttp(loop: false))->techtransfer()->patent('engine')->async())
        ->toThrow(StargazerException::class, 'loop');
});
