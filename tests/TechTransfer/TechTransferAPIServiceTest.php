<?php

use ProjectSaturnStudios\Stargazer\NasaClient;
use ProjectSaturnStudios\Stargazer\TechTransfer\DataObjects\TechTransferPage;
use ProjectSaturnStudios\Stargazer\TechTransfer\DataObjects\TechTransferRecord;
use ProjectSaturnStudios\Stargazer\TechTransfer\TechTransferArrived;
use ProjectSaturnStudios\Stargazer\TechTransfer\TechTransferFailed;
use Voyager\Http\Client\Factory;
use Voyager\IOPools\Presumption;
use Voyager\NutsAndBolts\MagicAliases\Http;

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
    $http = new Factory;
    $http->preventStrayRequests();
    $http->fake(fn () => Factory::response(techTransferFixture($file)));
    Http::swap($http);

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

beforeEach(function () {
    Http::clearResolvedInstances();
});

afterEach(function () {
    Http::clearResolvedInstances();
});

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

        return str_contains($url, '/techtransfer/patent')
            && str_contains($url, 'patent=engine')
            && str_contains($url, 'api_key=TEST_KEY');
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

        return str_contains($url, '/techtransfer/software')
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

        return str_contains($url, '/techtransfer/spinoff')
            && (str_contains($url, 'Spinoff=battery') || str_contains($url, 'spinoff=battery'));
    });
});

it('dispatches each TechTransfer async() builder under its namespaced call name', function (string $method, string $query, string $path, string $parameter) {
    [$dock, $driver] = stargazerDock();

    $presumption = stargazerClient(stargazerHttp(), $dock)->techtransfer()->{$method}($query)->async();

    expect($presumption)->toBeInstanceOf(Presumption::class)
        ->and($presumption->name)->toBe('stargazer.techtransfer.'.$method)
        ->and($driver->dispatched[0]['url'])->toContain($path)
        ->and($driver->dispatched[0]['url'])->toContain($parameter.'='.$query);
})->with([
    'patent' => ['patent', 'engine', '/techtransfer/patent', 'patent'],
    'software' => ['software', 'guidance', '/techtransfer/software', 'software'],
    'spinoff' => ['spinoff', 'battery', '/techtransfer/spinoff', 'Spinoff'],
]);

it('mails TechTransferArrived carrying the hydrated page through the dock', function () {
    [$dock, $driver] = stargazerDock();

    $presumption = stargazerClient(stargazerHttp(), $dock)->techtransfer()->patent('engine')->async();

    $driver->ready = [stargazerResult('stargazer.techtransfer.patent', techTransferFixture('patent.json'))];
    $dock->pump();

    $mail = $dock->drain()->sole();
    expect($mail)->toBeInstanceOf(TechTransferArrived::class)
        ->and($mail->page)->toBeInstanceOf(TechTransferPage::class)
        ->and($mail->page->results->first()->id)->toBe('64e71c1a64038afc1d0a01d2')
        ->and($presumption->settled())->toBeTrue();
});

it('mails TechTransferFailed on a sad conversation', function () {
    [$dock, $driver] = stargazerDock();

    stargazerClient(stargazerHttp(), $dock)->techtransfer()->patent('engine')->async();
    $driver->ready = [stargazerResult('stargazer.techtransfer.patent', 'gone', status: 502)];
    $dock->pump();

    $mail = $dock->drain()->sole();
    expect($mail)->toBeInstanceOf(TechTransferFailed::class)
        ->and($mail->ok())->toBeFalse()
        ->and($mail->reason)->toContain('502');
});
