<?php

use ProjectSaturnStudios\Stargazer\Exceptions\StargazerException;
use ProjectSaturnStudios\Stargazer\GIBS\DataObjects\GibsBox;
use ProjectSaturnStudios\Stargazer\GIBS\Enums\GibsProjection;
use ProjectSaturnStudios\Stargazer\GIBS\Enums\GibsTileFormat;
use ProjectSaturnStudios\Stargazer\GIBS\Twms\DataObjects\TwmsTileService;
use ProjectSaturnStudios\Stargazer\GIBS\Wms\DataObjects\WmsCapabilities;

/*
 * Fixtures are GIBS's own TWMS capabilities and GetTileService answer, cut to
 * two layers (each group to its first two patterns).
 */

it('fetches capabilities and the tile service', function () {
    $caps = gibsHttp(gibsFixture('twms-capabilities.xml'));
    $service = gibsHttp(gibsFixture('twms-tile-service.xml'));
    $read = stargazerClient($caps)->gibs()->twms(GibsProjection::EPSG3413)->capabilities()->get();
    $groups = stargazerClient($service)->gibs()->twms()->tileService()->get();

    expect($read)->toBeInstanceOf(WmsCapabilities::class)
        ->and(sentUrl($caps))->toBe('https://gibs.earthdata.nasa.gov/twms/epsg3413/best/twms.cgi?request=GetCapabilities')
        ->and($groups)->toBeInstanceOf(TwmsTileService::class)
        ->and(sentUrl($service))->toBe('https://gibs.earthdata.nasa.gov/twms/epsg4326/best/twms.cgi?request=GetTileService');
});

it('reads TWMS layers in the WMS 1.1.1 shape', function () {
    $modis = WmsCapabilities::fromXml(gibsFixture('twms-capabilities.xml'))->layer('MODIS_Terra_CorrectedReflectance_TrueColor');

    expect($modis->title)->toBe('Corrected Reflectance (True Color, MODIS, Terra)')
        ->and($modis->wgs84Box)->toEqual(new GibsBox(-180, -90, 180, 90))
        ->and($modis->styles[0]->name)->toBe('default');
});

it('reads tile groups and their patterns, timed and timeless', function () {
    $service = TwmsTileService::fromXml(gibsFixture('twms-tile-service.xml'));
    $modis = $service->group('MODIS_Terra_CorrectedReflectance_TrueColor');
    $marble = $service->group('BlueMarble_ShadedRelief_Bathymetry');
    $first = $marble->patterns[0];

    expect(array_keys($service->groups))->toBe(['BlueMarble_ShadedRelief_Bathymetry', 'MODIS_Terra_CorrectedReflectance_TrueColor'])
        ->and($modis->title)->toBe('Corrected Reflectance (True Color, MODIS, Terra)')
        ->and($modis->key)->toBe('${time}')
        ->and([$marble->pad, $marble->bands])->toBe([0, 3])
        ->and($marble->projection)->toStartWith('GEOGCS["WGS 84"')
        ->and($marble->patterns)->toHaveCount(2)
        ->and($first->box)->toEqual(new GibsBox(-180, 87.75, -177.75, 90))
        ->and([$first->width, $first->height])->toBe([512, 512])
        ->and($first->query(null))->toBe('request=GetMap&layers=BlueMarble_ShadedRelief_Bathymetry&srs=EPSG:4326&format=image/jpeg&styles=&width=512&height=512&bbox=-180.000000,87.750000,-177.750000,90.000000')
        ->and($modis->patterns[0]->query('2012-07-09'))->toContain('&time=2012-07-09&')
        ->and(fn () => $service->group('NOPE'))->toThrow(StargazerException::class, "no layer 'NOPE'");
});

it('asks for a tile by its pattern or by its box', function () {
    $http = gibsHttp('jpeg', 'image/jpeg');
    $twms = stargazerClient($http)->gibs()->twms();
    $pattern = TwmsTileService::fromXml(gibsFixture('twms-tile-service.xml'))->group('MODIS_Terra_CorrectedReflectance_TrueColor')->patterns[0];

    $twms->tile($pattern, '2012-07-09')->get();
    $twms->map('MODIS_Terra_CorrectedReflectance_TrueColor', new GibsBox(-18, 27, -13.5, 31.5), format: GibsTileFormat::JPEG, time: '2012-07-09')->get();

    $urls = [];
    $http->assertSent(function ($request) use (&$urls): bool {
        $urls[] = $request->url();

        return true;
    });
    expect($urls[0])->toStartWith('https://gibs.earthdata.nasa.gov/twms/epsg4326/best/twms.cgi?request=GetMap&layers=MODIS_Terra_CorrectedReflectance_TrueColor&srs=EPSG:4326&format=image/jpeg&styles=&time=2012-07-09&width=512&height=512&bbox=')
        ->and($urls[1])->toBe('https://gibs.earthdata.nasa.gov/twms/epsg4326/best/twms.cgi?request=GetMap&layers=MODIS_Terra_CorrectedReflectance_TrueColor&srs=EPSG:4326&format=image/jpeg&styles=&time=2012-07-09&width=512&height=512&bbox=-18,27,-13.5,31.5');
});

it('refuses a tile service document that is not one', function () {
    expect(fn () => TwmsTileService::fromXml('<Other/>'))->toThrow(StargazerException::class, 'its root is <Other>')
        ->and(fn () => TwmsTileService::fromXml(''))->toThrow(StargazerException::class, 'not readable XML');
});
