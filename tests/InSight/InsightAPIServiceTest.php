<?php

use ProjectSaturnStudios\Stargazer\InSight\DataObjects\InsightSol;
use ProjectSaturnStudios\Stargazer\InSight\DataObjects\InsightWeather;
use ProjectSaturnStudios\Stargazer\InSight\Enums\InsightSeason;
use ProjectSaturnStudios\Stargazer\InSight\InsightArrived;
use ProjectSaturnStudios\Stargazer\InSight\InsightFailed;
use ProjectSaturnStudios\Stargazer\NasaClient;
use Voyager\Http\Client\Factory;
use Voyager\IOPools\Presumption;
use Voyager\NutsAndBolts\MagicAliases\Http;

function insightFixture(string $file = 'weather.json'): array
{
    return json_decode(
        file_get_contents(dirname(__DIR__).'/Fixtures/InSight/'.$file),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );
}

function insightHttp(string $file = 'weather.json'): Factory
{
    $http = new Factory;
    $http->preventStrayRequests();
    $http->fake(fn () => Factory::response(insightFixture($file)));
    Http::swap($http);

    return $http;
}

beforeEach(function () {
    Http::clearResolvedInstances();
});

afterEach(function () {
    Http::clearResolvedInstances();
});

it('builds the InSight weather URL with the documented feed query', function () {
    $http = insightHttp();

    (new NasaClient(api_key: 'TEST_KEY', http: $http))->insight()->weather()->get();

    $http->assertSent(function ($request) {
        $url = $request->url();

        return str_contains($url, 'insight_weather')
            && str_contains($url, 'feedtype=json')
            && str_contains($url, 'ver=1.0')
            && str_contains($url, 'api_key=TEST_KEY');
    });
});

it('hydrates InSight weather sols from the captured fixture', function () {
    $payload = insightFixture();
    $http = insightHttp();

    $weather = (new NasaClient(api_key: 'TEST_KEY', http: $http))->insight()->weather()->get();

    expect($weather)->toBeInstanceOf(InsightWeather::class)
        ->and($weather->solKeys)->toBe($payload['sol_keys'])
        ->and($weather->sols)->toHaveCount(count($payload['sol_keys']))
        ->and($weather->sols->first())->toBeInstanceOf(InsightSol::class);

    $sol259 = $weather->sol('259');
    $raw = $payload['259'];

    expect($sol259)->not->toBeNull()
        ->and($sol259->sol)->toBe('259')
        ->and($sol259->season)->toBe(InsightSeason::WINTER)
        ->and($sol259->firstUtc)->toBe($raw['First_UTC'])
        ->and($sol259->lastUtc)->toBe($raw['Last_UTC'])
        ->and($sol259->temperature->average)->toBe($raw['AT']['av'])
        ->and($sol259->temperature->count)->toBe($raw['AT']['ct'])
        ->and($sol259->temperature->min)->toBe($raw['AT']['mn'])
        ->and($sol259->temperature->max)->toBe($raw['AT']['mx'])
        ->and($sol259->windSpeed->average)->toBe($raw['HWS']['av'])
        ->and($sol259->pressure->average)->toBe($raw['PRE']['av'])
        ->and($sol259->windDirection->mostCommon?->compassPoint)->toBe('SSW')
        ->and($sol259->windDirection->mostCommon?->count)->toBe(28551)
        ->and($sol259->windDirection->points)->toHaveCount(3);

    $sol260 = $weather->sol('260');

    expect($sol260->windSpeed)->toBeNull()
        ->and($sol260->windDirection->mostCommon)->toBeNull()
        ->and($weather->validity->hoursRequired)->toBe(18)
        ->and($weather->validity->solsChecked)->toBe($payload['validity_checks']['sols_checked'])
        ->and($weather->validity->forSol('260')->windSpeed->valid)->toBeFalse()
        ->and($weather->validity->forSol('259')->temperature->valid)->toBeTrue();
});

it('mails InsightArrived through the dock from async()', function () {
    [$dock, $driver] = stargazerDock();

    $presumption = stargazerClient(stargazerHttp(), $dock)->insight()->weather()->async();

    expect($presumption)->toBeInstanceOf(Presumption::class)
        ->and($presumption->name)->toBe('stargazer.insight.weather')
        ->and($driver->dispatched[0]['url'])->toContain('insight_weather')
        ->and($driver->dispatched[0]['url'])->toContain('feedtype=json');

    $driver->ready = [stargazerResult('stargazer.insight.weather', insightFixture())];
    $dock->pump();

    $mail = $dock->drain()->sole();
    expect($mail)->toBeInstanceOf(InsightArrived::class)
        ->and($mail->weather->solKeys)->not->toBeEmpty()
        ->and($mail->ok())->toBeTrue()
        ->and($presumption->settled())->toBeTrue();
});

it('mails InsightFailed on a sad conversation', function () {
    [$dock, $driver] = stargazerDock();

    stargazerClient(stargazerHttp(), $dock)->insight()->weather()->async();
    $driver->ready = [stargazerResult('stargazer.insight.weather', 'gone', status: 503)];
    $dock->pump();

    $mail = $dock->drain()->sole();
    expect($mail)->toBeInstanceOf(InsightFailed::class)
        ->and($mail->ok())->toBeFalse()
        ->and($mail->reason)->toContain('503');
});
