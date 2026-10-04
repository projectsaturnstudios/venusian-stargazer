<?php

use ProjectSaturnStudios\Stargazer\Exceptions\StargazerException;
use ProjectSaturnStudios\Stargazer\GIBS\Enums\GibsColorMapVersion;
use ProjectSaturnStudios\Stargazer\GIBS\Enums\GibsLegendFormat;
use ProjectSaturnStudios\Stargazer\GIBS\Enums\GibsLegendOrientation;
use ProjectSaturnStudios\Stargazer\GIBS\Metadata\GibsColorMapEntry;
use ProjectSaturnStudios\Stargazer\GIBS\Metadata\GibsColorMaps;
use ProjectSaturnStudios\Stargazer\GIBS\Metadata\GibsLayerMetadata;
use Voyager\Http\Client\Factory;
use Voyager\Http\Client\Response;

/*
 * Fixtures are GIBS's own files: a v1.3 colour map (aerosol optical depth, a
 * no-data map and a data map), a v1.0 one (air temperature), the MODIS Terra
 * true-colour layer metadata, FIRMS's vector metadata and a PNG legend.
 */

it('fetches a colour map in either schema', function (GibsColorMapVersion $version, string $fixture, string $url) {
    $http = gibsHttp(gibsFixture($fixture));
    $maps = stargazerClient($http)->gibs()->colormap('MODIS_Combined_Value_Added_AOD', $version)->get();

    expect($maps)->toBeInstanceOf(GibsColorMaps::class)
        ->and(sentUrl($http))->toBe($url);
})->with([
    'v1.3' => [GibsColorMapVersion::V1_3, 'colormap-v1.3.xml', 'https://gibs.earthdata.nasa.gov/colormaps/v1.3/MODIS_Combined_Value_Added_AOD.xml'],
    'v1.0' => [GibsColorMapVersion::V1_0, 'colormap-v1.0.xml', 'https://gibs.earthdata.nasa.gov/colormaps/v1.0/MODIS_Combined_Value_Added_AOD.xml'],
]);

it('reads a v1.3 colour map: no-data and data maps, entries, legend', function () {
    $maps = GibsColorMaps::fromXml(gibsFixture('colormap-v1.3.xml'));
    [$noData, $data] = $maps->maps;

    expect($maps->maps)->toHaveCount(2)
        ->and($noData->title)->toBe('No Data')
        ->and($noData->entries[0]->noData)->toBeTrue()
        ->and($noData->entries[0]->transparent)->toBeTrue()
        ->and($maps->data())->toBe($data)
        ->and($data->title)->toBe('Aerosol Optical Depth')
        ->and($data->entries[1]->rgb)->toBe([255, 252, 199])
        ->and($data->entries[1]->hex())->toBe('#fffcc7')
        ->and($data->entries[1]->sourceValue)->toBe('[0,5)')
        ->and($data->entries[1]->ref)->toBe('2')
        ->and($data->entryFor(0.0072)->value)->toBe('[0.005,0.010)')
        ->and($data->legendType)->toBe('continuous')
        ->and($data->minLabel)->toBe('< 0.0')
        ->and($data->legend[18]->tooltip)->toBe('0.085 – 0.090');
});

it('reads a v1.0 colour map: one map with units and labels', function () {
    $map = GibsColorMaps::fromXml(gibsFixture('colormap-v1.0.xml'))->maps[0];

    expect($map->units)->toBe('K')
        ->and($map->entries[0]->label)->toBe('No Data')
        ->and($map->entries[0]->transparent)->toBeTrue()
        ->and($map->entryFor(200.0)->label)->toBe('< 220 K')
        ->and($map->entryFor(1e6)->label)->toBe('>= 310 K')
        ->and($map->legend)->toBe([]);
});

it('reads data ranges with open and closed ends and infinities', function (string $range, float $value, bool $in) {
    expect((new GibsColorMapEntry([0, 0, 0], false, false, $range, null, null, null))->covers($value))->toBe($in);
})->with([
    'closed low end' => ['[0,5)', 0.0, true],
    'open high end' => ['[0,5)', 5.0, false],
    'open low end' => ['(0,5]', 0.0, false],
    'closed high end' => ['(0,5]', 5.0, true],
    'minus infinity' => ['[-INF,220)', -1e9, true],
    'plus infinity' => ['[310,+INF)', 1e9, true],
    'one value, closed' => ['[3,3]', 3.0, true],
    'empty' => ['[310,310)', 310.0, false],
]);

it('fetches a legend image in either orientation and format', function (GibsLegendOrientation $orientation, GibsLegendFormat $format, string $url) {
    $http = gibsHttp(gibsFixture('legend-h.png'), $format === GibsLegendFormat::PNG ? 'image/png' : 'image/svg+xml');
    $response = stargazerClient($http)->gibs()->legend('MERRA2_2m_Air_Temperature_Monthly', $orientation, $format)->get();

    expect($response)->toBeInstanceOf(Response::class)
        ->and(substr($response->body(), 1, 3))->toBe('PNG')
        ->and(sentUrl($http))->toBe($url);
})->with([
    'horizontal PNG' => [GibsLegendOrientation::HORIZONTAL, GibsLegendFormat::PNG, 'https://gibs.earthdata.nasa.gov/legends/MERRA2_2m_Air_Temperature_Monthly_H.png'],
    'vertical SVG' => [GibsLegendOrientation::VERTICAL, GibsLegendFormat::SVG, 'https://gibs.earthdata.nasa.gov/legends/MERRA2_2m_Air_Temperature_Monthly_V.svg'],
]);

it('reads layer metadata', function () {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response(json_decode(gibsFixture('layer-metadata.json'), true)));
    $metadata = stargazerClient($http)->gibs()->layerMetadata('MODIS_Terra_CorrectedReflectance_TrueColor')->get();

    expect($metadata)->toBeInstanceOf(GibsLayerMetadata::class)
        ->and(sentUrl($http))->toBe('https://gibs.earthdata.nasa.gov/layer-metadata/v1.0/MODIS_Terra_CorrectedReflectance_TrueColor.json')
        ->and($metadata->title)->toBe('Corrected Reflectance (True Color)')
        ->and($metadata->subtitle)->toBe('Terra / MODIS')
        ->and($metadata->ongoing)->toBeTrue()
        ->and($metadata->retentionPeriod)->toBe(-1)
        ->and($metadata->daynight)->toBe(['day'])
        ->and($metadata->conceptIds[0]->dataCenter)->toBe('LANCEMODIS')
        ->and($metadata->mvtProperties)->toBe([]);
});

it('reads vector metadata: each feature property and its value ranges', function () {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response(json_decode(gibsFixture('vector-metadata.json'), true)));
    $metadata = stargazerClient($http)->gibs()->vectorMetadata('FIRMS_MODIS_Thermal_Anomalies')->get();
    $brightness = $metadata->property('BRIGHTNESS');

    expect(sentUrl($http))->toBe('https://gibs.earthdata.nasa.gov/vector-metadata/v1.0/FIRMS_MODIS_Thermal_Anomalies.json')
        ->and($metadata->id)->toBe('FIRMS_MODIS_Thermal_Anomalies')
        ->and($metadata->property('LATITUDE')->units)->toBe('°')
        ->and($brightness->dataType)->toBe('float')
        ->and($brightness->function)->toBe('Style')
        ->and($brightness->valueRanges)->toBe([['Min' => 0, 'Max' => 500]])
        ->and($metadata->property('NOPE'))->toBeNull();
});

it('refuses a document that is not a colour map', function () {
    expect(fn () => GibsColorMaps::fromXml('<Other/>'))->toThrow(StargazerException::class, 'not a colour map');
});
