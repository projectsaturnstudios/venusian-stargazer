<?php

use ProjectSaturnStudios\Stargazer\DONKI\DataObjects\Cme;
use ProjectSaturnStudios\Stargazer\DONKI\DataObjects\CmeAnalysis;
use ProjectSaturnStudios\Stargazer\DONKI\DataObjects\Flare;
use ProjectSaturnStudios\Stargazer\DONKI\DataObjects\GeomagneticStorm;
use ProjectSaturnStudios\Stargazer\DONKI\DataObjects\HighSpeedStream;
use ProjectSaturnStudios\Stargazer\DONKI\DataObjects\InterplanetaryShock;
use ProjectSaturnStudios\Stargazer\DONKI\DataObjects\MagnetopauseCrossing;
use ProjectSaturnStudios\Stargazer\DONKI\DataObjects\Notification;
use ProjectSaturnStudios\Stargazer\DONKI\DataObjects\RadiationBeltEnhancement;
use ProjectSaturnStudios\Stargazer\DONKI\DataObjects\SolarEnergeticParticle;
use ProjectSaturnStudios\Stargazer\DONKI\DataObjects\WsaEnlilSimulation;
use ProjectSaturnStudios\Stargazer\DONKI\DonkiArrived;
use ProjectSaturnStudios\Stargazer\DONKI\DonkiFailed;
use ProjectSaturnStudios\Stargazer\DONKI\Enums\DonkiCatalog;
use ProjectSaturnStudios\Stargazer\DONKI\Enums\DonkiNotificationType;
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

dataset('donki list endpoints', [
    'cme' => ['cme', 'CME', Cme::class, 'activityID', '2016-09-02T06:18:00-CME-001'],
    'cmeAnalysis' => ['cmeAnalysis', 'CMEAnalysis', CmeAnalysis::class, 'associatedCMEID', '2016-09-06T08:54:00-CME-001'],
    'gst' => ['gst', 'GST', GeomagneticStorm::class, 'gstID', '2015-03-17T06:00:00-GST-001'],
    'ips' => ['ips', 'IPS', InterplanetaryShock::class, 'activityID', '2015-03-17T04:05:00-IPS-001'],
    'flr' => ['flr', 'FLR', Flare::class, 'flrID', '2017-09-06T08:57:00-FLR-001'],
    'sep' => ['sep', 'SEP', SolarEnergeticParticle::class, 'sepID', '2017-09-06T23:30:00-SEP-001'],
    'mpc' => ['mpc', 'MPC', MagnetopauseCrossing::class, 'mpcID', '2015-03-17T06:23:00-MPC-001'],
    'rbe' => ['rbe', 'RBE', RadiationBeltEnhancement::class, 'rbeID', '2015-03-19T10:40:00-RBE-001'],
    'hss' => ['hss', 'HSS', HighSpeedStream::class, 'hssID', '2016-09-01T10:00:00-HSS-001'],
    'wsaEnlilSimulations' => ['wsaEnlilSimulations', 'WSAEnlilSimulations', WsaEnlilSimulation::class, 'simulationID', 'WSA-ENLIL/10003/1'],
    'notifications' => ['notifications', 'notifications', Notification::class, 'messageID', '20140508-AL-002'],
]);

it('builds each DONKI endpoint URL and hydrates the captured fixture', function (string $method, string $path, string $dto, string $idField, string $id) {
    $fixture = stargazerFixture('DONKI', $method);
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response($fixture));

    $result = stargazerClient($http)->donki()->{$method}('2026-07-01', '2026-08-01')->get();

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result)->toHaveCount(1)
        ->and($result->first())->toBeInstanceOf($dto)
        ->and($result->first()->{$idField})->toBe($id);

    $http->assertSent(function ($request) use ($path) {
        $url = $request->url();

        return str_contains($url, '/DONKI/'.$path)
            && str_contains($url, 'startDate=2026-07-01')
            && str_contains($url, 'endDate=2026-08-01')
            && str_contains($url, 'api_key=TEST_KEY');
    });
})->with('donki list endpoints');

it('returns a Collection of CME DTOs from NASA::donki()->cme(\'2026-07-01\',\'2026-08-01\')->get()', function () {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response(stargazerFixture('DONKI', 'cme')));

    $result = stargazerClient($http)->donki()->cme('2026-07-01', '2026-08-01')->get();

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result->every(fn ($row) => $row instanceof Cme))->toBeTrue()
        ->and($result->first()->startTime)->toBe('2016-09-02T06:18Z')
        ->and($result->first()->instruments->first()->displayName)->toBe('SOHO: LASCO/C3')
        ->and($result->first()->cmeAnalyses->first()->isMostAccurate)->toBeTrue()
        ->and($result->first()->cmeAnalyses->first()->speed)->toBe(218.0)
        ->and($result->first()->linkedEvents)->toBeNull();
});

it('hydrates nested GST Kp index, linked events, and sent notifications from the fixture', function () {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response(stargazerFixture('DONKI', 'gst')));

    $storm = stargazerClient($http)->donki()->gst('2015-03-17', '2015-03-18')->get()->first();

    expect($storm)->toBeInstanceOf(GeomagneticStorm::class)
        ->and($storm->allKpIndex)->toHaveCount(8)
        ->and($storm->allKpIndex->first()->kpIndex)->toBe(6.0)
        ->and($storm->allKpIndex->first()->source)->toBe('NOAA')
        ->and($storm->linkedEvents->first()->activityID)->toBe('2015-03-15T02:00:00-CME-001')
        ->and($storm->sentNotifications->first()->messageID)->toBe('20150317-AL-002');
});

it('appends CME analysis catalog filters from DONKI docs', function () {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response(stargazerFixture('DONKI', 'cmeAnalysis')));

    stargazerClient($http)->donki()
        ->cmeAnalysis('2016-09-01', '2016-09-30')
        ->mostAccurateOnly(true)
        ->completeEntryOnly(true)
        ->speed(500)
        ->halfAngle(30)
        ->catalog(DonkiCatalog::ALL)
        ->keyword('swpc_annex')
        ->get();

    $http->assertSent(function ($request) {
        $url = $request->url();

        return str_contains($url, '/DONKI/CMEAnalysis')
            && str_contains($url, 'mostAccurateOnly=true')
            && str_contains($url, 'completeEntryOnly=true')
            && str_contains($url, 'speed=500')
            && str_contains($url, 'halfAngle=30')
            && str_contains($url, 'catalog=ALL')
            && str_contains($url, 'keyword=swpc_annex');
    });
});

it('appends IPS location and catalog filters from DONKI docs', function () {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response(stargazerFixture('DONKI', 'ips')));

    stargazerClient($http)->donki()
        ->ips('2015-03-17', '2015-03-18', 'Earth', DonkiCatalog::M2M_CATALOG)
        ->get();

    $http->assertSent(function ($request) {
        $url = $request->url();

        return str_contains($url, '/DONKI/IPS')
            && str_contains($url, 'location=Earth')
            && str_contains($url, 'catalog=M2M_CATALOG');
    });
});

it('appends flare class and catalog filters from DONKI docs', function () {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response(stargazerFixture('DONKI', 'flr')));

    $flare = stargazerClient($http)->donki()
        ->flr('2017-09-06', '2017-09-10', 'X', DonkiCatalog::M2M_CATALOG)
        ->get()
        ->first();

    expect($flare->classType)->toBe('X2.2')
        ->and($flare->beginTime)->toBe('2017-09-06T08:57Z')
        ->and($flare->sentNotifications)->toHaveCount(2);

    $http->assertSent(function ($request) {
        $url = $request->url();

        return str_contains($url, '/DONKI/FLR')
            && str_contains($url, 'class=X')
            && str_contains($url, 'catalog=M2M_CATALOG');
    });
});

it('appends the notifications type filter from DONKI docs', function () {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response(stargazerFixture('DONKI', 'notifications')));

    $note = stargazerClient($http)->donki()
        ->notifications('2014-05-01', '2014-05-08', DonkiNotificationType::ALL)
        ->get()
        ->first();

    expect($note)->toBeInstanceOf(Notification::class)
        ->and($note->messageType)->toBe('FLR')
        ->and($note->messageBody)->toContain('M5.2');

    $http->assertSent(fn ($request) => str_contains($request->url(), 'type=all'));
});

it('hydrates WSA-ENLIL cone inputs from the captured fixture', function () {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response(stargazerFixture('DONKI', 'wsaEnlilSimulations')));

    $sim = stargazerClient($http)->donki()->wsaEnlilSimulations('2016-01-06', '2016-01-06')->get()->first();

    expect($sim->au)->toBe(2.0)
        ->and($sim->isEarthGB)->toBeFalse()
        ->and($sim->cmeInputs->first()->cmeid)->toBe('2016-01-05T04:36:00-CME-001')
        ->and($sim->cmeInputs->first()->speed)->toBe(530.0)
        ->and($sim->estimatedShockArrivalTime)->toBeNull();
});

it('dispatches each DONKI async() builder under its namespaced call name', function (string $method, string $path) {
    [$dock, $driver] = stargazerDock();

    $presumption = stargazerClient(stargazerHttp(), $dock)->donki()->{$method}('2026-07-01', '2026-08-01')->async();

    expect($presumption)->toBeInstanceOf(Presumption::class)
        ->and($presumption->name)->toBe('stargazer.donki.'.$method)
        ->and($driver->dispatched[0]['method'])->toBe('GET')
        ->and($driver->dispatched[0]['url'])->toContain('/DONKI/'.$path)
        ->and($driver->dispatched[0]['url'])->toContain('api_key=TEST_KEY');
})->with([
    'cme' => ['cme', 'CME'],
    'cmeAnalysis' => ['cmeAnalysis', 'CMEAnalysis'],
    'gst' => ['gst', 'GST'],
    'ips' => ['ips', 'IPS'],
    'flr' => ['flr', 'FLR'],
    'sep' => ['sep', 'SEP'],
    'mpc' => ['mpc', 'MPC'],
    'rbe' => ['rbe', 'RBE'],
    'hss' => ['hss', 'HSS'],
    'wsaEnlilSimulations' => ['wsaEnlilSimulations', 'WSAEnlilSimulations'],
    'notifications' => ['notifications', 'notifications'],
]);

it('mails DonkiArrived carrying hydrated rows through the dock', function () {
    [$dock, $driver] = stargazerDock();

    $presumption = stargazerClient(stargazerHttp(), $dock)->donki()->cme('2026-07-01', '2026-08-01')->async();

    $driver->ready = [stargazerResult('stargazer.donki.cme', stargazerFixture('DONKI', 'cme'))];
    $dock->pump();

    $mail = $dock->drain()->sole();
    expect($mail)->toBeInstanceOf(DonkiArrived::class)
        ->and($mail->items[0])->toBeInstanceOf(Cme::class)
        ->and($mail->items[0]->activityID)->toBe('2016-09-02T06:18:00-CME-001')
        ->and($presumption->settled())->toBeTrue();
});

it('mails DonkiFailed on a sad conversation', function () {
    [$dock, $driver] = stargazerDock();

    stargazerClient(stargazerHttp(), $dock)->donki()->cme('2026-07-01', '2026-08-01')->async();
    $driver->ready = [stargazerResult('stargazer.donki.cme', 'gone', status: 502)];
    $dock->pump();

    $mail = $dock->drain()->sole();
    expect($mail)->toBeInstanceOf(DonkiFailed::class)
        ->and($mail->ok())->toBeFalse()
        ->and($mail->reason)->toContain('502');
});
