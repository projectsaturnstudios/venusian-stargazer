<?php

use ProjectSaturnStudios\Stargazer\Enums\NasaURL;
use ProjectSaturnStudios\Stargazer\Exceptions\NotYetSupportedException;
use ProjectSaturnStudios\Stargazer\Exoplanet\ExoplanetArchive;
use ProjectSaturnStudios\Stargazer\GIBS\GibsAPIService;
use ProjectSaturnStudios\Stargazer\NasaClient;
use ProjectSaturnStudios\Stargazer\OpenScience\OpenScienceAPIService;
use ProjectSaturnStudios\Stargazer\SSC\SscAPIService;
use ProjectSaturnStudios\Stargazer\SSD\SsdCneosAPIService;
use ProjectSaturnStudios\Stargazer\Techport\TechportAPIService;
use ProjectSaturnStudios\Stargazer\Trek\TrekWmtsAPIService;

it('throws NotYetSupportedException from every deferred API stub', function (string $class, string $name) {
    expect(fn () => new $class())
        ->toThrow(NotYetSupportedException::class, $name.' is not yet supported by Stargazer.');
})->with([
    'GIBS' => [GibsAPIService::class, 'GIBS'],
    'Trek WMTS' => [TrekWmtsAPIService::class, 'Trek WMTS'],
    'Exoplanet Archive' => [ExoplanetArchive::class, 'Exoplanet Archive'],
    'Open Science Data Repository' => [OpenScienceAPIService::class, 'Open Science Data Repository'],
    'Satellite Situation Center' => [SscAPIService::class, 'Satellite Situation Center'],
    'SSD/CNEOS' => [SsdCneosAPIService::class, 'SSD/CNEOS'],
    'Techport' => [TechportAPIService::class, 'Techport'],
]);

it('exposes deferred accessors that throw immediately', function (string $method, string $name) {
    expect(fn () => (new NasaClient)->{$method}())
        ->toThrow(NotYetSupportedException::class, $name.' is not yet supported by Stargazer.');
})->with([
    'gibs' => ['gibs', 'GIBS'],
    'trek' => ['trek', 'Trek WMTS'],
    'exoplanet' => ['exoplanet', 'Exoplanet Archive'],
    'openScience' => ['openScience', 'Open Science Data Repository'],
    'ssc' => ['ssc', 'Satellite Situation Center'],
    'ssd' => ['ssd', 'SSD/CNEOS'],
    'techport' => ['techport', 'Techport'],
]);

it('catalogues every deferred host on NasaURL', function (NasaURL $case, string $host) {
    expect($case->name)->toMatch('/^[A-Z][A-Z0-9_]*$/')
        ->and($case->value)->toStartWith('https://')
        ->and($case->value)->toContain($host);
})->with([
    'GIBS' => [NasaURL::GIBS, 'gibs.earthdata.nasa.gov'],
    'TREK_WMTS' => [NasaURL::TREK_WMTS, 'trek.nasa.gov'],
    'EXOPLANET' => [NasaURL::EXOPLANET, 'exoplanetarchive.ipac.caltech.edu'],
    'OPEN_SCIENCE' => [NasaURL::OPEN_SCIENCE, 'osdr.nasa.gov'],
    'SATELLITE_SITUATION_CENTER' => [NasaURL::SATELLITE_SITUATION_CENTER, 'sscweb.gsfc.nasa.gov'],
    'SSD_CNEOS' => [NasaURL::SSD_CNEOS, 'ssd-api.jpl.nasa.gov'],
    'TECHPORT' => [NasaURL::TECHPORT, 'api.nasa.gov/techport'],
]);
