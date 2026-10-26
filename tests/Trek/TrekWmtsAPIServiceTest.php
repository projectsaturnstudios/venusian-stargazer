<?php

use ProjectSaturnStudios\Stargazer\Exceptions\StargazerException;
use ProjectSaturnStudios\Stargazer\GIBS\Wmts\DataObjects\WmtsCapabilities;
use ProjectSaturnStudios\Stargazer\Trek\DataObjects\TrekLayer;
use ProjectSaturnStudios\Stargazer\Trek\Enums\TrekBody;
use ProjectSaturnStudios\Stargazer\Trek\Enums\TrekProjection;
use ProjectSaturnStudios\Stargazer\Trek\TrekWmtsAPIService;
use Voyager\Http\Client\Response;
use Voyager\NutsAndBolts\Collection;

/*
 * Fixtures are Trek's own answers: three Moon mosaics from searchItems,
 * Vesta's fourteen layers from listVisibleLayers, and the WMTS capabilities of
 * the LRO WAC global mosaic.
 */

function trekFixture(string $name): string
{
    return (string) file_get_contents(dirname(__DIR__).'/Fixtures/Trek/'.$name);
}

/** The mosaic facets Trek's own API page asks for. */
const TREK_FACETS = 'facetKeys=itemType%7CproductCat2%7CserviceTypes%7CthumbnailURLDir&facetValues=product%7C-%28DEM%20ConfidenceRaw%20Slope%29%7CMosaic%7C%5B%2A%20TO%20%2A%5D';

it('answers a TrekWmtsAPIService from the client', function () {
    expect(stargazerClient(gibsHttp('{}', 'application/json'))->trek())->toBeInstanceOf(TrekWmtsAPIService::class);
});

it('lists a body\'s mosaics from searchItems, with no API key', function () {
    $http = gibsHttp(trekFixture('moon-layers.json'), 'application/json');

    $layers = stargazerClient($http)->trek()->layers(TrekBody::MOON)->get();
    $first = $layers[0];

    expect($layers)->toBeInstanceOf(Collection::class)
        ->and($layers)->toHaveCount(3)
        ->and($first)->toBeInstanceOf(TrekLayer::class)
        ->and($first->label)->toBe('Apollo15_MetricCam_ClrShade_Global_1024ppd')
        ->and($first->title)->toBe('Apollo 15 Metric Cam DEM, ColorHillshade')
        ->and($first->body)->toBe(TrekBody::MOON)
        ->and($first->projection)->toBe(TrekProjection::EQUIRECTANGULAR)
        ->and($first->box?->toString())->toBe('-65.9995117,-31.9995117,179.9995117,32.0004883')
        ->and($first->mission)->toBe('Apollo15')
        ->and($first->instrument)->toBe('Metric Camera')
        ->and($first->category)->toBe('Topography')
        ->and($first->kind)->toBe('ColorHillshade')
        ->and($first->coverage)->toBe('Global')
        ->and($first->thumbnailUrl)->toBe('https://trek.nasa.gov/tiles/Moon/EQ/Apollo15_MetricCam_ClrShade_Global_1024ppd/thumbnail/Apollo15_MetricCam_ClrShade_Global_1024ppd-120.png')
        ->and(sentUrl($http))->toBe('https://trek.nasa.gov/moon/TrekServices/ws/index/eq/searchItems?start=0&rows=2147483647&'.TREK_FACETS);
});

it('asks a polar list for its CRS', function () {
    $http = gibsHttp('{"response":{"docs":[]}}', 'application/json');

    stargazerClient($http)->trek()->layers(TrekBody::MARS, TrekProjection::SOUTH_POLE)->get();

    expect(sentUrl($http))->toBe('https://trek.nasa.gov/mars/TrekServices/ws/index/polar/searchItems?start=0&rows=2147483647&'.TREK_FACETS.'&proj=urn%3Aogc%3Adef%3Acrs%3AIAU2000%3A%3A49920');
});

it('lists Vesta from listVisibleLayers', function () {
    $http = gibsHttp(trekFixture('vesta-layers.json'), 'application/json');

    $layers = stargazerClient($http)->trek()->layers(TrekBody::VESTA)->get();

    // listVisibleLayers ignores rows: the fixture asked for 2 and got all 14.
    expect($layers)->toHaveCount(14)
        ->and($layers[0]->label)->toBe('VestaHAMOLAMOBlend.eq')
        ->and($layers[0]->body)->toBe(TrekBody::VESTA)
        ->and(sentUrl($http))->toBe('https://trek.nasa.gov/vesta/TrekServices/ws/index/eq/listVisibleLayers?proj=urn%3Aogc%3Adef%3Acrs%3AEPSG%3A%3A2000004%CE%A9&start=0&rows=2147483647');
});

it('skips a row with no layer label', function () {
    $json = json_encode(['response' => ['docs' => [['title' => 'no label'], ['productLabel' => 'kept', 'title' => 'Kept']]]]);

    $layers = stargazerClient(gibsHttp($json, 'application/json'))->trek()->layers(TrekBody::MOON)->get();

    expect($layers)->toHaveCount(1)
        ->and($layers[0]->label)->toBe('kept')
        ->and($layers[0]->box)->toBeNull()
        ->and($layers[0]->thumbnailUrl)->toBeNull();
});

it('reads one layer\'s WMTS capabilities with the GIBS parser', function () {
    $http = gibsHttp(trekFixture('moon-capabilities.xml'), 'application/xml');

    $caps = stargazerClient($http)->trek()->capabilities(TrekBody::MOON, TrekProjection::EQUIRECTANGULAR, 'LRO_WAC_Mosaic_Global_303ppd_v02')->get();
    $layer = $caps->layer('LRO_WAC_Mosaic_Global_303ppd_v02');

    expect($caps)->toBeInstanceOf(WmtsCapabilities::class)
        ->and($layer->formats)->toBe(['image/jpeg'])
        ->and($layer->tileMatrixSets)->toBe(['default028mm'])
        ->and($caps->tileMatrixSetOf($layer->identifier)->zoomLevels())->toBe(9)
        ->and(sentUrl($http))->toBe('https://trek.nasa.gov/tiles/Moon/EQ/LRO_WAC_Mosaic_Global_303ppd_v02/1.0.0/WMTSCapabilities.xml');
});

it('fetches one tile by its REST path', function () {
    $http = gibsHttp('jpeg bytes', 'image/jpeg');

    $tile = stargazerClient($http)->trek()->tile(TrekBody::MARS, TrekProjection::NORTH_POLE, 'Mars_MGS_MOLA_ClrShade_NPole', 3, 1, 2, 'png')->get();

    expect($tile)->toBeInstanceOf(Response::class)
        ->and($tile->body())->toBe('jpeg bytes')
        ->and(sentUrl($http))->toBe('https://trek.nasa.gov/tiles/Mars/NP/Mars_MGS_MOLA_ClrShade_NPole/1.0.0/default/default028mm/3/1/2.png');
});

it('throws for a tile with no coverage', function () {
    $http = gibsHttp('<html>Not Found</html>', 'text/html', 404);

    expect(fn () => stargazerClient($http)->trek()->tile(TrekBody::MOON, TrekProjection::EQUIRECTANGULAR, 'LRO_WAC_Mosaic_Global_303ppd_v02', 0, 5, 5)->get())
        ->toThrow(StargazerException::class, 'NASA request failed (404)');
});

it('knows each body\'s folder and CRS for each projection', function (TrekBody $body, string $folder, string $eq, string $np, string $sp) {
    expect($body->folder())->toBe($folder)
        ->and($body->crs(TrekProjection::EQUIRECTANGULAR))->toBe($eq)
        ->and($body->crs(TrekProjection::NORTH_POLE))->toBe($np)
        ->and($body->crs(TrekProjection::SOUTH_POLE))->toBe($sp);
})->with([
    'Moon' => [TrekBody::MOON, 'Moon', 'urn:ogc:def:crs:EPSG::104903', 'urn:ogc:def:crs:IAU2000::30118', 'urn:ogc:def:crs:IAU2000::30120'],
    'Mars' => [TrekBody::MARS, 'Mars', 'urn:ogc:def:crs:IAU2000::104905', 'urn:ogc:def:crs:IAU2000::49918', 'urn:ogc:def:crs:IAU2000::49920'],
    'Vesta' => [TrekBody::VESTA, 'Vesta', 'urn:ogc:def:crs:EPSG::2000004Ω', 'urn:ogc:def:crs:trek::vesta_np', 'urn:ogc:def:crs:trek::vesta_sp'],
]);
