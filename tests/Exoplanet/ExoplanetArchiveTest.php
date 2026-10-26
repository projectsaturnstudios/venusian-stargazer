<?php

use ProjectSaturnStudios\Stargazer\Exceptions\StargazerException;
use ProjectSaturnStudios\Stargazer\Exoplanet\DataObjects\Exoplanet;
use ProjectSaturnStudios\Stargazer\Exoplanet\ExoplanetArchive;
use Voyager\NutsAndBolts\Collection;

/*
 * Fixtures are the archive's own answers: three planets from pscomppars
 * (one with null temperatures), two raw rows, and the VOTable a bad
 * column gets.
 */

function exoplanetFixture(string $name): string
{
    return (string) file_get_contents(dirname(__DIR__).'/Fixtures/Exoplanet/'.$name);
}

/** @return array<string, string> The query string of the one request sent. */
function sentQuery(Voyager\Http\Client\Factory $http): array
{
    parse_str((string) parse_url(sentUrl($http), PHP_URL_QUERY), $query);

    return $query;
}

it('answers an ExoplanetArchive from the client', function () {
    expect(stargazerClient(gibsHttp('[]', 'application/json'))->exoplanet())->toBeInstanceOf(ExoplanetArchive::class);
});

it('runs any ADQL on TAP sync as JSON, with no API key', function () {
    $http = gibsHttp(exoplanetFixture('rows.json'), 'application/json');

    $rows = stargazerClient($http)->exoplanet()->adql("select hostname, sy_pnum from pscomppars where hostname = 'TRAPPIST-1'", limit: 2)->get();

    expect($rows)->toBeInstanceOf(Collection::class)
        ->and($rows->all())->toBe([['hostname' => 'TRAPPIST-1', 'sy_pnum' => 7], ['hostname' => 'TRAPPIST-1', 'sy_pnum' => 7]])
        ->and(strtok(sentUrl($http), '?'))->toBe('https://exoplanetarchive.ipac.caltech.edu/TAP/sync')
        ->and(sentQuery($http))->toBe([
            'query' => "select hostname, sy_pnum from pscomppars where hostname = 'TRAPPIST-1'",
            'format' => 'json',
            'maxrec' => '2',
        ]);
});

it('leaves maxrec off when there is no limit', function () {
    $http = gibsHttp('[]', 'application/json');

    stargazerClient($http)->exoplanet()->adql('select pl_name from ps')->get();

    expect(sentQuery($http))->not->toHaveKey('maxrec');
});

it('hydrates planets from pscomppars, nulls kept as null', function () {
    $planets = stargazerClient(gibsHttp(exoplanetFixture('pscomppars.json'), 'application/json'))->exoplanet()->planets()->get();
    $kepler = $planets[1];
    $pulsar = $planets[2];

    expect($planets)->toHaveCount(3)
        ->and($kepler)->toBeInstanceOf(Exoplanet::class)
        ->and($kepler->name)->toBe('Kepler-22 b')
        ->and($kepler->host)->toBe('Kepler-22')
        ->and($kepler->discoveryMethod)->toBe('Transit')
        ->and($kepler->discoveryYear)->toBe(2011)
        ->and($kepler->orbitalPeriodDays)->toBe(289.863876)
        ->and($kepler->radiusEarth)->toBe(2.1)
        ->and($kepler->massEarth)->toBe(9.1)
        ->and($kepler->distanceParsecs)->toBe(194.642)
        ->and($kepler->equilibriumKelvin)->toBe(279.0)
        ->and($kepler->starKelvin)->toBe(5596.0)
        ->and($kepler->ra)->toBe(289.2172051)
        ->and($kepler->dec)->toBe(47.8841427)
        ->and($pulsar->equilibriumKelvin)->toBeNull()
        ->and($pulsar->starKelvin)->toBeNull();
});

it('selects the planet columns, then the filter and order, limited by maxrec not TOP', function () {
    $http = gibsHttp('[]', 'application/json');

    stargazerClient($http)->exoplanet()->planets(where: 'disc_year >= 2026', orderBy: 'pl_name', limit: 5)->get();
    $query = sentQuery($http);

    expect($query['query'])->toBe('select '.implode(',', Exoplanet::COLUMNS).' from pscomppars where disc_year >= 2026 order by pl_name')
        ->and($query['query'])->not->toContain('top')
        ->and($query['maxrec'])->toBe('5');
});

it('orders planets newest first by default', function () {
    $http = gibsHttp('[]', 'application/json');

    stargazerClient($http)->exoplanet()->planets()->get();

    expect(sentQuery($http)['query'])->toEndWith('from pscomppars order by disc_year desc, pl_name');
});

it('finds one planet by name, its quotes doubled', function () {
    $http = gibsHttp(exoplanetFixture('pscomppars.json'), 'application/json');

    $planet = stargazerClient($http)->exoplanet()->planet("Kepler-22 b' or 'x'='x")->get();

    expect($planet)->toBeInstanceOf(Exoplanet::class)
        ->and($planet->name)->toBe('55 Cnc e')
        ->and(sentQuery($http)['query'])->toEndWith("where pl_name = 'Kepler-22 b'' or ''x''=''x'")
        ->and(sentQuery($http)['maxrec'])->toBe('1');
});

it('answers null for a planet the archive does not have', function () {
    expect(stargazerClient(gibsHttp('[]', 'application/json'))->exoplanet()->planet('Nowhere b')->get())->toBeNull();
});

it('throws the archive reason when it rejects a query', function () {
    $http = gibsHttp(exoplanetFixture('error.xml'), 'application/xml', 400);

    expect(fn () => stargazerClient($http)->exoplanet()->adql('select nope from pscomppars')->get())
        ->toThrow(StargazerException::class, "ORA-00904: 'NOPE': invalid identifier");
});
