<?php

use ProjectSaturnStudios\Stargazer\Exceptions\StargazerException;
use ProjectSaturnStudios\Stargazer\GIBS\DataObjects\GibsBox;
use ProjectSaturnStudios\Stargazer\GIBS\Enums\GibsImagerySet;
use ProjectSaturnStudios\Stargazer\GIBS\Enums\GibsMapFormat;
use ProjectSaturnStudios\Stargazer\GIBS\Enums\GibsProjection;
use ProjectSaturnStudios\Stargazer\GIBS\Enums\WmsVersion;
use ProjectSaturnStudios\Stargazer\GIBS\Wms\DataObjects\WmsCapabilities;
use Voyager\Http\Client\Response;

/*
 * Fixtures are GIBS's own WMS 1.3.0 and 1.1.1 capabilities cut to a few layers
 * (a group kept around its members), and the two exception shapes WMS sends:
 * an OWS ExceptionReport (status 400) and a ServiceExceptionReport (status 200).
 */

it('fetches capabilities in either version', function (WmsVersion $version, string $fixture) {
    $http = gibsHttp(gibsFixture($fixture));
    $pending = stargazerClient($http)->gibs()->wms(GibsProjection::EPSG4326, GibsImagerySet::STANDARD)->capabilities($version);

    expect($pending->get()->version)->toBe($version->value)
        ->and(sentUrl($http))->toBe("https://gibs.earthdata.nasa.gov/wms/epsg4326/std/wms.cgi?SERVICE=WMS&REQUEST=GetCapabilities&VERSION={$version->value}")
        ->and($pending->timeoutSeconds())->toBe(120.0);
})->with([
    '1.3.0' => [WmsVersion::V1_3_0, 'wms-130.xml'],
    '1.1.1' => [WmsVersion::V1_1_1, 'wms-111.xml'],
]);

it('reads layers at any depth, with their group, time, CRS and legends', function () {
    $caps = WmsCapabilities::fromXml(gibsFixture('wms-130.xml'));
    $modis = $caps->layer('MODIS_Terra_CorrectedReflectance_TrueColor');
    $merra = $caps->layer('MERRA2_2m_Air_Temperature_Monthly');
    $ozone = $caps->layer('DISCOVER-AQ_CA_P3B_Ozone');

    expect($caps->title)->toContain('Global Imagery Browse Services')
        ->and($caps->mapFormats)->toContain('image/png', 'image/jpeg', 'image/tiff')
        ->and($caps->layers)->toHaveKey('NASA_GIBS_EPSG4326_best')
        ->and($caps->layer('NASA_GIBS_EPSG4326_best')->isGroup())->toBeTrue()
        ->and($modis->crs)->toBe(['EPSG:4326', 'EPSG:3857'])
        ->and($modis->wgs84Box)->toEqual(new GibsBox(-180, -90, 180, 90))
        ->and($modis->time->default)->toBe('2026-10-04')
        ->and($modis->time->periods)->toHaveCount(9)
        ->and($modis->time->includes('2012-07-09'))->toBeTrue()
        ->and($modis->group)->not->toBeNull()
        ->and($merra->time->periods[0]->period)->toBe('P1M')
        ->and($merra->legend()->href)->toBe('https://gibs.earthdata.nasa.gov/legends/MERRA2_2m_Air_Temperature_Monthly_H.png')
        ->and($merra->legend('default')->width)->toBe(378)
        ->and($ozone->legend()->href)->toContain('request=GetLegendGraphic');
});

it('reads 1.1.1 times from <Extent> and boxes from LatLonBoundingBox', function () {
    $modis = WmsCapabilities::fromXml(gibsFixture('wms-111.xml'))->layer('MODIS_Terra_CorrectedReflectance_TrueColor');

    expect($modis->crs)->toBe(['EPSG:4326', 'EPSG:3857'])
        ->and($modis->wgs84Box)->toEqual(new GibsBox(-180, -90, 180, 90))
        ->and($modis->time->default)->toBe('2026-10-04')
        ->and($modis->time->periods[0]->start)->toBe('2000-02-24');
});

it('builds GetMap: latitude first only for 1.3.0 in EPSG:4326, SRS for 1.1.1', function (array $arguments, string $url) {
    $http = gibsHttp('png', 'image/png');
    $response = stargazerClient($http)->gibs()->wms(...$arguments[0])->map(...$arguments[1])->get();

    expect($response)->toBeInstanceOf(Response::class)
        ->and(sentUrl($http))->toBe($url);
})->with([
    '1.3.0 geographic' => [[[], ['MODIS_Terra_CorrectedReflectance_TrueColor', new GibsBox(-180, -90, 180, 90), 720, 360, GibsMapFormat::JPEG, '2021-09-21']],
        'https://gibs.earthdata.nasa.gov/wms/epsg4326/best/wms.cgi?SERVICE=WMS&REQUEST=GetMap&VERSION=1.3.0&LAYERS=MODIS_Terra_CorrectedReflectance_TrueColor&STYLES=&CRS=EPSG:4326&BBOX=-90,-180,90,180&WIDTH=720&HEIGHT=360&FORMAT=image/jpeg&TIME=2021-09-21'],
    '1.1.1 geographic' => [[[], ['BlueMarble_ShadedRelief_Bathymetry', new GibsBox(-180, -90, 180, 90), 512, 256, GibsMapFormat::PNG, null, WmsVersion::V1_1_1]],
        'https://gibs.earthdata.nasa.gov/wms/epsg4326/best/wms.cgi?SERVICE=WMS&REQUEST=GetMap&VERSION=1.1.1&LAYERS=BlueMarble_ShadedRelief_Bathymetry&STYLES=&SRS=EPSG:4326&BBOX=-180,-90,180,90&WIDTH=512&HEIGHT=256&FORMAT=image/png'],
    '1.3.0 polar, stacked, transparent' => [[[GibsProjection::EPSG3413], [['MODIS_Terra_CorrectedReflectance_TrueColor', 'Coastlines_15m'], new GibsBox(-4194304, -4194304, 4194304, 4194304), 512, 512, GibsMapFormat::PNG, '2021-09-21', WmsVersion::V1_3_0, true, ['default', 'default']]],
        'https://gibs.earthdata.nasa.gov/wms/epsg3413/best/wms.cgi?SERVICE=WMS&REQUEST=GetMap&VERSION=1.3.0&LAYERS=MODIS_Terra_CorrectedReflectance_TrueColor,Coastlines_15m&STYLES=default,default&CRS=EPSG:3413&BBOX=-4194304,-4194304,4194304,4194304&WIDTH=512&HEIGHT=512&FORMAT=image/png&TRANSPARENT=TRUE&TIME=2021-09-21'],
]);

it('builds GetLegendGraphic', function () {
    $http = gibsHttp('png', 'image/png');
    stargazerClient($http)->gibs()->wms()->legendGraphic('DISCOVER-AQ_CA_P3B_Ozone')->get();

    expect(sentUrl($http))->toBe('https://gibs.earthdata.nasa.gov/wms/epsg4326/best/wms.cgi?SERVICE=WMS&REQUEST=GetLegendGraphic&VERSION=1.3.0&LAYER=DISCOVER-AQ_CA_P3B_Ozone&STYLE=default&FORMAT=image/png&SLD_VERSION=1.1.0');
});

it('refuses either exception shape in an image\'s place', function (string $fixture, int $status, string $message) {
    $http = gibsHttp(gibsFixture($fixture), 'text/xml; charset=UTF-8', $status);

    expect(fn () => stargazerClient($http)->gibs()->wms()->legendGraphic('NOPE')->get())->toThrow(StargazerException::class, $message);
})->with([
    'ServiceExceptionReport, 200' => ['wms-service-exception.xml', 200, 'LayerNotDefined: msWMSGetLegendGraphic(): WMS server error. Invalid layer given'],
    'ExceptionReport, 400' => ['wms-exception.xml', 400, '(400)'],
]);

it('refuses a document that is not WMS capabilities', function () {
    expect(fn () => WmsCapabilities::fromXml('<Other/>'))->toThrow(StargazerException::class, 'its root is <Other>')
        ->and(fn () => WmsCapabilities::fromXml(gibsFixture('wms-exception.xml')))->toThrow(StargazerException::class, 'InvalidParameterValue');
});
