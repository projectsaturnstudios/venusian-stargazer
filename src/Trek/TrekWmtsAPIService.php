<?php

namespace ProjectSaturnStudios\Stargazer\Trek;

use ProjectSaturnStudios\Stargazer\Enums\NasaPayload;
use ProjectSaturnStudios\Stargazer\Enums\NasaURL;
use ProjectSaturnStudios\Stargazer\GIBS\Wmts\DataObjects\WmtsCapabilities;
use ProjectSaturnStudios\Stargazer\NasaApiService;
use ProjectSaturnStudios\Stargazer\PendingNasaRequest;
use ProjectSaturnStudios\Stargazer\Trek\DataObjects\TrekLayer;
use ProjectSaturnStudios\Stargazer\Trek\Enums\TrekBody;
use ProjectSaturnStudios\Stargazer\Trek\Enums\TrekProjection;
use Voyager\NutsAndBolts\Collection;

/**
 * Solar System Treks: each body's layer list, and each layer's RESTful WMTS. Trek's
 * capabilities are standard WMTS, so the GIBS parser reads them.
 */
class TrekWmtsAPIService extends NasaApiService
{
    /** Every row at once, as Trek's own API page asks. */
    public const int ALL_ROWS = 2147483647;

    /** Trek's API-page facets: products that are mosaics, without raw DEM, confidence or slope, that have a thumbnail. */
    public const string MOSAIC_FACETS = 'facetKeys=itemType%7CproductCat2%7CserviceTypes%7CthumbnailURLDir&facetValues=product%7C-%28DEM%20ConfidenceRaw%20Slope%29%7CMosaic%7C%5B%2A%20TO%20%2A%5D';

    /** The layers $body shows in $projection. Answers a Collection of TrekLayer. */
    public function layers(TrekBody $body, TrekProjection $projection = TrekProjection::EQUIRECTANGULAR): PendingNasaRequest
    {
        $index = $body->value.'/TrekServices/ws/index/'.$projection->index();
        $proj = 'proj='.rawurlencode($body->crs($projection));
        $rows = 'start=0&rows='.self::ALL_ROWS;
        $path = $body->listsVisibleLayers()
            ? $index.'/listVisibleLayers?'.$proj.'&'.$rows
            : $index.'/searchItems?'.$rows.'&'.self::MOSAIC_FACETS.($projection === TrekProjection::EQUIRECTANGULAR ? '' : '&'.$proj);

        return $this->pending(
            base: NasaURL::TREK_WMTS,
            path: $path,
            call_name: 'stargazer.trek.layers',
            hydrator: fn (mixed $json): Collection => Collection::make((array) ($json['response']['docs'] ?? []))
                ->filter(fn (mixed $row): bool => is_array($row) && ($row['productLabel'] ?? '') !== '')
                ->map(fn (array $row): TrekLayer => TrekLayer::fromRow($row, $body, $projection))
                ->values(),
        );
    }

    /** One layer's WMTS capabilities. Answers WmtsCapabilities. */
    public function capabilities(TrekBody $body, TrekProjection $projection, string $label): PendingNasaRequest
    {
        $path = $this->root($body, $projection, $label).'/WMTSCapabilities.xml';

        return $this->pending(
            base: NasaURL::TREK_WMTS,
            path: $path,
            call_name: 'stargazer.trek.capabilities',
            hydrator: fn (string $xml): WmtsCapabilities => WmtsCapabilities::fromXml($xml, $path),
            payload: NasaPayload::XML,
        );
    }

    /**
     * One tile. Answers the Http Response (body() is the image). A tile outside the layer's
     * coverage is Trek's 404, which throws StargazerException.
     *
     * @param  string  $extension  jpg or png: the layer's ResourceURL template says which.
     */
    public function tile(
        TrekBody $body,
        TrekProjection $projection,
        string $label,
        int $zoom,
        int $row,
        int $col,
        string $extension = 'jpg',
        string $tileMatrixSet = 'default028mm',
        string $style = 'default',
    ): PendingNasaRequest {
        $segments = array_map(fn (string|int $s): string => rawurlencode((string) $s), [$style, $tileMatrixSet, $zoom, $row, $col.'.'.$extension]);

        return $this->pending(
            base: NasaURL::TREK_WMTS,
            path: $this->root($body, $projection, $label).'/'.implode('/', $segments),
            call_name: 'stargazer.trek.tile',
            payload: NasaPayload::BYTES,
        );
    }

    private function root(TrekBody $body, TrekProjection $projection, string $label): string
    {
        return 'tiles/'.$body->folder().'/'.$projection->value.'/'.rawurlencode($label).'/1.0.0';
    }
}
