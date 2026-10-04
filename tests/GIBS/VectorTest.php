<?php

use ProjectSaturnStudios\Stargazer\Exceptions\StargazerException;
use ProjectSaturnStudios\Stargazer\GIBS\Vector\GibsGeometryType;
use ProjectSaturnStudios\Stargazer\GIBS\Vector\GibsStyleColor;
use ProjectSaturnStudios\Stargazer\GIBS\Vector\GibsStyleExpression;
use ProjectSaturnStudios\Stargazer\GIBS\Vector\GibsVectorDictionary;
use ProjectSaturnStudios\Stargazer\GIBS\Vector\GibsVectorFeature;
use ProjectSaturnStudios\Stargazer\GIBS\Vector\GibsVectorStyle;
use ProjectSaturnStudios\Stargazer\GIBS\Vector\GibsVectorTile;
use Tests\Support\MvtWriter;
use Voyager\Http\Client\Factory;

/*
 * Synthetic tiles come from MvtWriter, so each geometry and value type is
 * exact; fires.mvt is a live GIBS tile (VIIRS NOAA-20 fires, 2020-10-01,
 * gzipped as GIBS sends them). The styles are GIBS's own.
 */

function syntheticTile(): string
{
    return MvtWriter::write([
        'places' => [
            ['id' => 7, 'type' => 1, 'parts' => [[[10, 20]], [[4000, 4090]]], 'properties' => ['name' => 'two', 'count' => 3, 'below' => -5, 'share' => 0.25, 'open' => true]],
        ],
        'shapes' => [
            ['type' => 2, 'parts' => [[[0, 0], [100, 50], [200, 0]], [[300, 300], [310, 310]]]],
            ['type' => 3, 'parts' => [
                [[0, 0], [100, 0], [100, 100], [0, 100]],        // exterior: clockwise with y down
                [[20, 20], [20, 80], [80, 80], [80, 20]],         // hole: the other way
                [[200, 200], [300, 200], [300, 300], [200, 300]],  // a second polygon
            ]],
        ],
    ], extent: 4096);
}

it('reads layers, features, ids, every value type and point, line and polygon geometry', function () {
    $tile = GibsVectorTile::fromBytes(syntheticTile());
    [$point] = $tile->layer('places')->features;
    [$line, $polygon] = $tile->layer('shapes')->features;

    expect(array_keys($tile->layers))->toBe(['places', 'shapes'])
        ->and($tile->layer('places')->extent)->toBe(4096)
        ->and($tile->layer('places')->version)->toBe(2)
        ->and($point->id)->toBe(7)
        ->and($point->type)->toBe(GibsGeometryType::POINT)
        ->and($point->properties())->toBe(['name' => 'two', 'count' => 3, 'below' => -5, 'share' => 0.25, 'open' => true])
        ->and($point->parts())->toBe([[[10, 20]], [[4000, 4090]]])
        ->and($line->id)->toBeNull()
        ->and($line->type)->toBe(GibsGeometryType::LINESTRING)
        ->and($line->parts())->toBe([[[0, 0], [100, 50], [200, 0]], [[300, 300], [310, 310]]])
        ->and($polygon->type)->toBe(GibsGeometryType::POLYGON)
        ->and($polygon->parts())->toHaveCount(3)
        ->and($tile->layer('nope'))->toBeNull();
});

it('adds no vertex for a step that does not move, but keeps every point', function () {
    $tile = GibsVectorTile::fromBytes(MvtWriter::write(['still' => [
        ['type' => 2, 'parts' => [[[5, 5], [5, 5], [5, 5], [9, 5], [9, 5]]]],
        ['type' => 1, 'parts' => [[[3, 3]], [[3, 3]]]],
    ]]));
    [$line, $points] = $tile->layer('still')->features;

    expect($line->parts())->toBe([[[5, 5], [9, 5]]])
        ->and($points->parts())->toBe([[[3, 3]], [[3, 3]]]);
});

it('groups polygon rings: each exterior with the holes after it', function () {
    [, $polygon] = GibsVectorTile::fromBytes(syntheticTile())->layer('shapes')->features;
    $polygons = $polygon->polygons();

    expect($polygons)->toHaveCount(2)
        ->and($polygons[0])->toHaveCount(2)
        ->and($polygons[1])->toHaveCount(1)
        ->and(GibsVectorFeature::area($polygons[0][0]))->toBeGreaterThan(0)
        ->and(GibsVectorFeature::area($polygons[0][1]))->toBeLessThan(0);
});

it('reads a live, gzipped GIBS tile', function () {
    $tile = GibsVectorTile::fromBytes(gibsFixture('fires.mvt'));
    $layer = $tile->layer('VIIRS_NOAA20_Thermal_Anomalies_375m_All_v2_NRT');
    $first = $layer->features[0];

    expect(substr(gibsFixture('fires.mvt'), 0, 2))->toBe("\x1f\x8b")
        ->and($layer->features)->toHaveCount(843)
        ->and($first->type)->toBe(GibsGeometryType::POINT)
        ->and($first->properties()['ACQ_DATE'])->toBe('2020-10-01')
        ->and($first->properties()['BRIGHT_TI4'])->toEqualWithDelta(328.8, 1e-4)
        ->and($first->parts())->toBe([[[3923, -50]]]);
});

it('keeps each feature encoded until asked, and says when its tags name what the layer lacks', function () {
    $dictionary = new GibsVectorDictionary(['k'], ['v']);
    $good = new GibsVectorFeature(null, GibsGeometryType::POINT, "\x00\x00", "\x09\x04\x06", $dictionary);
    $bad = new GibsVectorFeature(null, GibsGeometryType::POINT, "\x00\x05", "\x09\x04\x06", $dictionary);

    expect($good->properties())->toBe(['k' => 'v'])
        ->and($good->parts())->toBe([[[2, 3]]])
        ->and(fn () => $bad->properties())->toThrow(StargazerException::class, 'names key 0 or value 5, which its layer lacks');
});

it('fetches and decodes a vector tile', function () {
    $http = gibsHttp(gibsFixture('fires.mvt'), 'application/octet');
    $tile = stargazerClient($http)->gibs()->wmts()->vectorTile('VIIRS_NOAA20_Thermal_Anomalies_375m_All', '500m', 4, 3, 4, '2020-10-01')->get();

    expect($tile)->toBeInstanceOf(GibsVectorTile::class)
        ->and(sentUrl($http))->toBe('https://gibs.earthdata.nasa.gov/wmts/epsg4326/best/VIIRS_NOAA20_Thermal_Anomalies_375m_All/default/2020-10-01/500m/4/3/4.mvt');
});

it('refuses bytes that are not a vector tile', function (string $bytes, string $message) {
    expect(fn () => GibsVectorTile::fromBytes($bytes))->toThrow(StargazerException::class, $message);
})->with([
    'damaged gzip' => ["\x1f\x8b\x08\x00garbage", 'gzip wrapping is damaged'],
    'cut-off field' => ["\x1a\x10abc", 'runs past the end'],
    'unknown wire type' => ["\x1b", 'wire type 3'],
]);

it('fetches and reads a vector style', function () {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response(json_decode(gibsFixture('style-firms.json'), true)));
    $style = stargazerClient($http)->gibs()->vectorStyle('FIRMS_VIIRS_Thermal_Anomalies')->get();

    expect($style)->toBeInstanceOf(GibsVectorStyle::class)
        ->and(sentUrl($http))->toBe('https://gibs.earthdata.nasa.gov/vector-styles/v1.0/FIRMS_VIIRS_Thermal_Anomalies.json')
        ->and($style->version)->toBe(8)
        ->and($style->name)->toBe('FIRMS_SNPP_Thermal_Anomalies')
        ->and($style->sources['VIIRS_SNPP_Thermal_Anomalies_375m_All'][0])->toContain('{TileMatrixSet}/{TileMatrix}/{TileRow}/{TileCol}.mvt')
        ->and($style->layersFor('VIIRS_NOAA20_Thermal_Anomalies_375m_All_v2_NRT'))->not->toBe([]);
});

it('evaluates GIBS style properties for a feature at a zoom', function () {
    $fires = GibsVectorStyle::fromArray(json_decode(gibsFixture('style-firms.json'), true));
    $borders = GibsVectorStyle::fromArray(json_decode(gibsFixture('style-boundaries.json'), true));
    $grid = GibsVectorStyle::fromArray(json_decode(gibsFixture('style-grid.json'), true));
    $fire = new GibsVectorFeature(null, GibsGeometryType::POINT, [], [[[0, 0]]]);
    $rank3 = new GibsVectorFeature(null, GibsGeometryType::LINESTRING, ['RANK' => '3'], []);
    $rank2 = new GibsVectorFeature(null, GibsGeometryType::LINESTRING, ['RANK' => '2'], []);
    $circle = $fires->layersFor('VIIRS_NOAA20_Thermal_Anomalies_375m_All_v2_NRT')[0];
    [$glow, $core] = $borders->layers;

    expect($circle->type)->toBe('circle')
        ->and($circle->paint('circle-radius', $fire, 0))->toBe(1)
        ->and($circle->paint('circle-radius', $fire, 2))->toBe(2)
        ->and($circle->paint('circle-radius', $fire, 7))->toBe(3)
        ->and(GibsStyleColor::parse($circle->paint('circle-color', $fire, 3)))->not->toBeNull()
        ->and($circle->paint('circle-stroke-width', $fire, 3, 0))->toBe(0)
        ->and($circle->draws($fire, 3))->toBeTrue()
        ->and($glow->paint('line-width', $rank3, 3))->toEqual(1)
        ->and($glow->paint('line-width', $rank2, 4))->toEqualWithDelta(3.75, 1e-9)
        ->and($glow->paint('line-color', $rank3, 3))->toBe([16.0, 18.0, 18.0, 0.1])
        ->and($core->paint('line-dasharray', $rank2, 3))->toBe([6, 4])
        ->and($grid->layers[0]->layout('text-font', $fire, 3))->toBe(['Open Sans Bold', 'Arial Unicode MS Bold']);
});

it('evaluates expressions', function (mixed $expression, mixed $expected, array $properties = [], float $zoom = 5) {
    expect(GibsStyleExpression::evaluate($expression, $zoom, $properties, GibsGeometryType::POLYGON))->toEqual($expected);
})->with([
    'a plain value' => [3, 3],
    'literal' => [['literal', [1, 2]], [1, 2]],
    'get' => [['get', 'a'], 'x', ['a' => 'x']],
    'get, missing' => [['get', 'b'], null],
    'has' => [['has', 'a'], true, ['a' => 0]],
    'not' => [['!', ['has', 'a']], true],
    'equal numbers, int and float' => [['==', ['get', 'n'], 3], true, ['n' => 3.0]],
    'a string is not a number' => [['==', ['get', 's'], 3], false, ['s' => '3']],
    'less, numbers' => [['<', ['get', 'n'], 4], true, ['n' => 3]],
    'less, null' => [['<', ['get', 'none'], 4], false],
    'all, any' => [['all', true, ['any', false, true]], true],
    'case' => [['case', ['>', ['get', 'n'], 5], 'big', 'small'], 'small', ['n' => 3]],
    'match, list label' => [['match', ['get', 'k'], ['a', 'b'], 1, 'c', 2, 0], 1, ['k' => 'b']],
    'match, fallback' => [['match', ['get', 'k'], 'a', 1, 0], 0, ['k' => 'z']],
    'step below the first stop' => [['step', ['zoom'], 'a', 6, 'b'], 'a'],
    'step past a stop' => [['step', ['zoom'], 'a', 3, 'b', 9, 'c'], 'b'],
    'interpolate linear' => [['interpolate', ['linear'], ['zoom'], 0, 0, 10, 100], 50.0],
    'interpolate exponential' => [['interpolate', ['exponential', 2], ['zoom'], 0, 0, 10, 1023], 31.0],
    'interpolate colours' => [['interpolate', ['linear'], ['zoom'], 0, '#000000', 10, '#ffffff'], [127.5, 127.5, 127.5, 1.0]],
    'interpolate below and above' => [['interpolate', ['linear'], ['get', 'v'], 1, 10, 2, 20], 20, ['v' => 9]],
    'zoom' => [['zoom'], 5.0],
    'geometry-type' => [['geometry-type'], 'Polygon'],
    'modulo' => [['%', ['get', 'n'], 4], 3.0, ['n' => 7]],
    'arithmetic' => [['-', ['+', 1, 2, 3], ['*', 2, ['/', 6, 3]]], 2.0],
    'rgba' => [['rgba', 1, 2, 3, 0.5], [1.0, 2.0, 3.0, 0.5]],
    'coalesce' => [['coalesce', ['get', 'none'], 'fallback'], 'fallback'],
    'concat, upcase' => [['upcase', ['concat', 'a', ['get', 'n']]], 'A1', ['n' => 1]],
    'to-number' => [['to-number', ['get', 's']], 2.5, ['s' => '2.5']],
]);

it('refuses an operator it does not evaluate', function () {
    expect(fn () => GibsStyleExpression::evaluate(['within', []], 1))->toThrow(StargazerException::class, "operator 'within'");
});

it('reads every colour notation GIBS styles use', function (mixed $colour, ?array $rgba) {
    expect(GibsStyleColor::parse($colour))->toBe($rgba);
})->with([
    '#fff' => ['#fff', [255.0, 255.0, 255.0, 1.0]],
    '#rrggbb' => ['#102030', [16.0, 32.0, 48.0, 1.0]],
    'rgb with spaces' => ['rgb( 11,43,92)', [11.0, 43.0, 92.0, 1.0]],
    'rgba' => ['rgba(0,0,0,0.7)', [0.0, 0.0, 0.0, 0.7]],
    'an rgba expression result' => [[16, 18, 18, 0.4], [16.0, 18.0, 18.0, 0.4]],
    'not a colour' => ['blue-ish', null],
]);
