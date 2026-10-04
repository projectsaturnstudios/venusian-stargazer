<?php

use ProjectSaturnStudios\Stargazer\Exceptions\StargazerException;
use ProjectSaturnStudios\Stargazer\GIBS\DataObjects\GibsBox;
use ProjectSaturnStudios\Stargazer\GIBS\Enums\GibsImagerySet;
use ProjectSaturnStudios\Stargazer\GIBS\Enums\GibsProjection;
use ProjectSaturnStudios\Stargazer\GIBS\Enums\GibsRequestStyle;
use ProjectSaturnStudios\Stargazer\GIBS\Enums\GibsTileFormat;
use ProjectSaturnStudios\Stargazer\GIBS\Wmts\DataObjects\WmtsCapabilities;
use ProjectSaturnStudios\Stargazer\GIBS\Wmts\DataObjects\WmtsDomains;
use Voyager\Http\Client\Factory;
use Voyager\Http\Client\Response;

/*
 * Fixtures are GIBS's own documents: the EPSG:4326 and EPSG:3413 "best"
 * capabilities cut to a few layers (every tile matrix set kept),
 * DescribeDomains answers, and the exception report a bad layer gets.
 */

it('fetches WMTS capabilities RESTfully or by KVP, with room for GIBS to take its time', function (GibsRequestStyle $style, string $url) {
    $http = gibsHttp(gibsFixture('wmts-epsg4326.xml'));
    $pending = stargazerClient($http)->gibs()->wmts()->capabilities($style);

    expect($pending->get())->toBeInstanceOf(WmtsCapabilities::class)
        ->and(sentUrl($http))->toBe($url)
        ->and($pending->timeoutSeconds())->toBe(120.0);
})->with([
    'REST' => [GibsRequestStyle::REST, 'https://gibs.earthdata.nasa.gov/wmts/epsg4326/best/1.0.0/WMTSCapabilities.xml'],
    'KVP' => [GibsRequestStyle::KVP, 'https://gibs.earthdata.nasa.gov/wmts/epsg4326/best/wmts.cgi?SERVICE=WMTS&REQUEST=GetCapabilities&VERSION=1.0.0'],
]);

it('reads the projection and imagery set into every URL', function () {
    $http = gibsHttp(gibsFixture('wmts-epsg3413.xml'));
    stargazerClient($http)->gibs()->wmts(GibsProjection::EPSG3413, GibsImagerySet::NEAR_REAL_TIME)->capabilities()->get();

    expect(sentUrl($http))->toBe('https://gibs.earthdata.nasa.gov/wmts/epsg3413/nrt/1.0.0/WMTSCapabilities.xml');
});

it('reads layers: formats, sets, time, templates, styles, legends and links', function () {
    $caps = WmtsCapabilities::fromXml(gibsFixture('wmts-epsg4326.xml'));
    $modis = $caps->layer('MODIS_Terra_CorrectedReflectance_TrueColor');
    $merra = $caps->layer('MERRA2_2m_Air_Temperature_Monthly');
    $fires = $caps->layer('VIIRS_NOAA20_Thermal_Anomalies_375m_All');
    $marble = $caps->layer('BlueMarble_ShadedRelief');

    expect($caps->title)->toBe('NASA Global Imagery Browse Services for EOSDIS')
        ->and(array_keys($caps->layers))->toHaveCount(4)
        ->and($modis->title)->toBe('Corrected Reflectance (True Color, MODIS, Terra)')
        ->and($modis->formats)->toBe(['image/jpeg'])
        ->and($modis->tileFormat())->toBe(GibsTileFormat::JPEG)
        ->and($modis->tileMatrixSets)->toBe(['250m'])
        ->and($modis->wgs84Box)->toEqual(new GibsBox(-180, -90, 180, 90))
        ->and($modis->time->periods[0]->start)->toBe('2000-02-24')
        ->and($modis->time->includes('2012-07-09'))->toBeTrue()
        ->and($modis->time->includes('2000-04-26'))->toBeFalse()
        ->and($modis->tileResource(true)->template)->toBe('https://gibs.earthdata.nasa.gov/wmts/epsg4326/best/MODIS_Terra_CorrectedReflectance_TrueColor/default/{Time}/{TileMatrixSet}/{TileMatrix}/{TileRow}/{TileCol}.jpeg')
        ->and($modis->tileResource(false)->template)->toBe('https://gibs.earthdata.nasa.gov/wmts/epsg4326/best/MODIS_Terra_CorrectedReflectance_TrueColor/default/{TileMatrixSet}/{TileMatrix}/{TileRow}/{TileCol}.jpeg')
        ->and($merra->time->periods[0]->period)->toBe('P1M')
        ->and($merra->time->default)->toBe('2026-06-01')
        ->and($merra->defaultStyle()->legend('horizontal')->href)->toBe('https://gibs.earthdata.nasa.gov/legends/MERRA2_2m_Air_Temperature_Monthly_H.svg')
        ->and($merra->defaultStyle()->legend('vertical')->width)->toBe(135)
        ->and($merra->linkId('colormap/1.3'))->toBe('MERRA2_2m_Air_Temperature_Monthly')
        ->and($fires->isVector())->toBeTrue()
        ->and($fires->tileFormat())->toBe(GibsTileFormat::MVT)
        ->and($fires->linkId('mapbox-gl-style/1.0'))->toBe('FIRMS_VIIRS_Thermal_Anomalies')
        ->and($fires->link('layer/1.0'))->toBe('https://gibs.earthdata.nasa.gov/vector-metadata/v1.0/FIRMS_VIIRS_Thermal_Anomalies.json')
        ->and($marble->time)->toBeNull()
        ->and($marble->isVector())->toBeFalse();
});

it('reads tile matrix sets and does their geometry in degrees or metres', function () {
    $geographic = WmtsCapabilities::fromXml(gibsFixture('wmts-epsg4326.xml'))->tileMatrixSet('250m');
    $polar = WmtsCapabilities::fromXml(gibsFixture('wmts-epsg3413.xml'))->tileMatrixSet('250m');
    $top = $geographic->matrix(0);

    expect(array_keys(WmtsCapabilities::fromXml(gibsFixture('wmts-epsg4326.xml'))->tileMatrixSets))->toBe(['16km', '2km', '1km', '500m', '250m', '31.25m', '15.625m'])
        ->and($geographic->zoomLevels())->toBe(9)
        ->and($top->pixelSpan())->toEqualWithDelta(0.5625, 1e-9)
        ->and([$top->matrixWidth, $top->matrixHeight, $top->tileWidth])->toBe([2, 1, 512])
        ->and($top->tileBox(0, 1))->toEqual(new GibsBox(108.0, -198.0, 396.0, 90.0))
        ->and($geographic->matrix(8)->pixelSpan())->toEqualWithDelta(0.5625 / 256, 1e-12)
        ->and($geographic->matrix(9))->toBeNull()
        ->and($polar->crs)->toBe('urn:ogc:def:crs:EPSG::3413')
        ->and($polar->matrix(0)->pixelSpan())->toEqualWithDelta(8192.0, 1e-6)
        ->and($polar->matrix(0)->tileBox(1, 1))->toEqual(new GibsBox(0.0, -4194304.0, 4194304.0, 0.0));
});

it('finds the tiles a box touches, edges not reaching the next tile, clipped to the grid', function () {
    $zoom2 = WmtsCapabilities::fromXml(gibsFixture('wmts-epsg4326.xml'))->tileMatrixSet('250m')->matrix(2);
    $tile = 512 * $zoom2->pixelSpan();     // 72 degrees

    $range = $zoom2->covering(new GibsBox(-180 + $tile, 90 - 2 * $tile, -180 + 3 * $tile, 90 - $tile));

    expect($tile)->toEqualWithDelta(72.0, 1e-9)
        ->and([$range->firstRow, $range->lastRow, $range->firstCol, $range->lastCol])->toBe([1, 1, 1, 2])
        ->and($range->tiles())->toBe([[1, 1], [1, 2]])
        ->and($zoom2->covering(new GibsBox(-180, -90, 180, 90))->count())->toBe(15)
        ->and($zoom2->covering(new GibsBox(200, 0, 250, 10)))->toBeNull();
});

it('builds RESTful and KVP tile URLs, with or without a time', function (GibsRequestStyle $style, ?string $time, GibsTileFormat $format, string $url) {
    $http = gibsHttp('tile', $format->value);
    $response = stargazerClient($http)->gibs()->wmts()->tile('MODIS_Terra_CorrectedReflectance_TrueColor', '250m', 6, 13, 36, $format, $time, $style)->get();

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->body())->toBe('tile')
        ->and(sentUrl($http))->toBe($url);
})->with([
    'REST, dated' => [GibsRequestStyle::REST, '2012-07-09', GibsTileFormat::JPEG, 'https://gibs.earthdata.nasa.gov/wmts/epsg4326/best/MODIS_Terra_CorrectedReflectance_TrueColor/default/2012-07-09/250m/6/13/36.jpeg'],
    'REST, timeless' => [GibsRequestStyle::REST, null, GibsTileFormat::PNG, 'https://gibs.earthdata.nasa.gov/wmts/epsg4326/best/MODIS_Terra_CorrectedReflectance_TrueColor/default/250m/6/13/36.png'],
    'REST, vector' => [GibsRequestStyle::REST, '2020-10-01', GibsTileFormat::MVT, 'https://gibs.earthdata.nasa.gov/wmts/epsg4326/best/MODIS_Terra_CorrectedReflectance_TrueColor/default/2020-10-01/250m/6/13/36.mvt'],
    'KVP, dated' => [GibsRequestStyle::KVP, '2012-07-09', GibsTileFormat::JPEG, 'https://gibs.earthdata.nasa.gov/wmts/epsg4326/best/wmts.cgi?SERVICE=WMTS&REQUEST=GetTile&VERSION=1.0.0&LAYER=MODIS_Terra_CorrectedReflectance_TrueColor&STYLE=default&TILEMATRIXSET=250m&TILEMATRIX=6&TILEROW=13&TILECOL=36&FORMAT=image/jpeg&TIME=2012-07-09'],
]);

it('refuses an exception report in a tile\'s place, with or without an error status', function (int $status) {
    $http = gibsHttp(gibsFixture('wmts-exception.xml'), 'text/xml', $status);

    expect(fn () => stargazerClient($http)->gibs()->wmts()->tile('NOPE', '250m', 6, 13, 36, time: '2012-07-09')->get())
        ->toThrow(StargazerException::class, $status === 200 ? 'InvalidParameterValue: LAYER does not exist' : '(400)');
})->with([200, 400]);

it('refuses an exception report in place of capabilities', function () {
    $http = gibsHttp(gibsFixture('wmts-exception.xml'));

    expect(fn () => stargazerClient($http)->gibs()->wmts()->capabilities()->get())
        ->toThrow(StargazerException::class, 'GIBS refused wmts/epsg4326/best/1.0.0/WMTSCapabilities.xml: InvalidParameterValue: LAYER does not exist');
});

it('builds DescribeDomains URLs both ways and reads the answer', function (GibsRequestStyle $style, array $limits, string $url) {
    $http = gibsHttp(gibsFixture('domains-time.xml'));
    $domains = stargazerClient($http)->gibs()->wmts()->domains('MODIS_Terra_CorrectedReflectance_TrueColor', '250m', ...$limits, style: $style)->get();

    expect($domains)->toBeInstanceOf(WmtsDomains::class)
        ->and(sentUrl($http))->toBe($url);
})->with([
    'REST, everything' => [GibsRequestStyle::REST, [], 'https://gibs.earthdata.nasa.gov/wmts/epsg4326/best/1.0.0/MODIS_Terra_CorrectedReflectance_TrueColor/default/250m/all/all.xml'],
    'REST, a range in a box' => [GibsRequestStyle::REST, ['box' => new GibsBox(-10, -10, 10, 10), 'from' => '2020-01-01', 'to' => '2020-03-01'], 'https://gibs.earthdata.nasa.gov/wmts/epsg4326/best/1.0.0/MODIS_Terra_CorrectedReflectance_TrueColor/default/250m/-10%2C-10%2C10%2C10/2020-01-01--2020-03-01.xml'],
    'REST, onward' => [GibsRequestStyle::REST, ['from' => '2026-09-01'], 'https://gibs.earthdata.nasa.gov/wmts/epsg4326/best/1.0.0/MODIS_Terra_CorrectedReflectance_TrueColor/default/250m/all/2026-09-01.xml'],
    'REST, up to' => [GibsRequestStyle::REST, ['to' => '2000-03-01'], 'https://gibs.earthdata.nasa.gov/wmts/epsg4326/best/1.0.0/MODIS_Terra_CorrectedReflectance_TrueColor/default/250m/all/--2000-03-01.xml'],
    'KVP, up to' => [GibsRequestStyle::KVP, ['to' => '2000-03-01'], 'https://gibs.earthdata.nasa.gov/wmts/epsg4326/best/wmts.cgi?SERVICE=WMTS&REQUEST=DescribeDomains&VERSION=1.0.0&LAYER=MODIS_Terra_CorrectedReflectance_TrueColor&TILEMATRIXSET=250m&DOMAINS=bbox,time&TIME=/2000-03-01'],
    'KVP, a range' => [GibsRequestStyle::KVP, ['from' => '2020-01-01', 'to' => '2020-03-01'], 'https://gibs.earthdata.nasa.gov/wmts/epsg4326/best/wmts.cgi?SERVICE=WMTS&REQUEST=DescribeDomains&VERSION=1.0.0&LAYER=MODIS_Terra_CorrectedReflectance_TrueColor&TILEMATRIXSET=250m&DOMAINS=bbox,time&TIME=2020-01-01/2020-03-01'],
]);

it('reads where and when a layer has data', function () {
    $all = WmtsDomains::fromXml(gibsFixture('domains-all.xml'));
    $range = WmtsDomains::fromXml(gibsFixture('domains-time.xml'));

    expect($all->box)->toEqual(new GibsBox(-180, -90, 180, 90))
        ->and($all->crs)->toBe('urn:ogc:def:crs:OGC:2:84')
        ->and($all->size)->toBe(9)
        ->and($all->periods)->toHaveCount(9)
        ->and($all->periods[0]->start)->toBe('2000-02-24')
        ->and($range->box)->toBeNull()
        ->and($range->periods[0]->end)->toBe('2020-03-01')
        ->and($range->periods[0]->period)->toBe('P1D');
});

it('streams a capabilities document that is not one into an error', function () {
    expect(fn () => WmtsCapabilities::fromXml('<Other/>'))->toThrow(StargazerException::class, 'its root is <Other>')
        ->and(fn () => WmtsCapabilities::fromXml('<Capabilities><Contents>'))->toThrow(StargazerException::class, 'not readable XML')
        ->and(fn () => WmtsCapabilities::fromXml(''))->toThrow(StargazerException::class, 'not readable XML');
});
