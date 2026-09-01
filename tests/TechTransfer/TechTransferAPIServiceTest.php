<?php

use ProjectSaturnStudios\Stargazer\NasaClient;
use ProjectSaturnStudios\Stargazer\TechTransfer\DataObjects\TechTransferPage;
use ProjectSaturnStudios\Stargazer\TechTransfer\DataObjects\TechTransferRecord;
use Voyager\Contracts\IOPools\HttpDriver;
use Voyager\Http\Client\Factory;
use Voyager\IOPools\EventQueue;
use Voyager\IOPools\HttpPool;
use Voyager\IOPools\PendingCall;
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

function techTransferPool(): array
{
    $driver = new class implements HttpDriver
    {
        public array $dispatched = [];

        public function dispatch(string $name, string $method, string $url, array $headers, ?string $body): void
        {
            $this->dispatched[] = compact('name', 'method', 'url', 'headers', 'body');
        }

        public function harvest(): array
        {
            return [];
        }

        public function progress(): array
        {
            return [];
        }
    };

    return [$driver, new HttpPool($driver, new EventQueue)];
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

it('returns a namespaced PendingCall from TechTransfer async()', function () {
    $http = techTransferHttp('patent.json');
    [$driver, $pool] = techTransferPool();

    $call = (new NasaClient(api_key: 'TEST_KEY', http: $http, pool: $pool))
        ->techtransfer()
        ->patent('engine')
        ->async();

    expect($call)->toBeInstanceOf(PendingCall::class)
        ->and($call->name)->toBe('stargazer.techtransfer.patent')
        ->and($driver->dispatched[0]['url'])->toContain('/techtransfer/patent')
        ->and($driver->dispatched[0]['url'])->toContain('patent=engine');
});
