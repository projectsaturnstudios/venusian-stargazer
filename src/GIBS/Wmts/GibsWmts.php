<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Wmts;

use ProjectSaturnStudios\Stargazer\Enums\NasaPayload;
use ProjectSaturnStudios\Stargazer\Enums\NasaURL;
use ProjectSaturnStudios\Stargazer\GIBS\DataObjects\GibsBox;
use ProjectSaturnStudios\Stargazer\GIBS\Enums\GibsImagerySet;
use ProjectSaturnStudios\Stargazer\GIBS\Enums\GibsProjection;
use ProjectSaturnStudios\Stargazer\GIBS\Enums\GibsRequestStyle;
use ProjectSaturnStudios\Stargazer\GIBS\Enums\GibsTileFormat;
use ProjectSaturnStudios\Stargazer\GIBS\Support\GibsUrl;
use ProjectSaturnStudios\Stargazer\GIBS\Support\GibsXml;
use ProjectSaturnStudios\Stargazer\GIBS\Vector\GibsVectorTile;
use ProjectSaturnStudios\Stargazer\GIBS\Wmts\DataObjects\WmtsCapabilities;
use ProjectSaturnStudios\Stargazer\GIBS\Wmts\DataObjects\WmtsDomains;
use ProjectSaturnStudios\Stargazer\NasaApiService;
use ProjectSaturnStudios\Stargazer\NasaClient;
use ProjectSaturnStudios\Stargazer\PendingNasaRequest;
use Voyager\Http\Client\Response;

/**
 * GIBS WMTS for one projection and imagery set: GetCapabilities, GetTile and
 * DescribeDomains, each RESTful (path segments) or KVP (wmts.cgi query).
 */
class GibsWmts extends NasaApiService
{
    /** Seconds a capabilities fetch may take: GIBS has taken 33 s to build one. */
    public const float CAPABILITIES_TIMEOUT = 120.0;

    public function __construct(
        NasaClient $client,
        public readonly GibsProjection $projection = GibsProjection::EPSG4326,
        public readonly GibsImagerySet $set = GibsImagerySet::BEST,
    ) {
        parent::__construct($client);
    }

    /** Answers WmtsCapabilities. Its raw XML (for a cache) is WmtsCapabilities::fromXml()'s input. */
    public function capabilities(GibsRequestStyle $style = GibsRequestStyle::REST): PendingNasaRequest
    {
        $path = $style === GibsRequestStyle::REST
            ? $this->root().'/1.0.0/WMTSCapabilities.xml'
            : GibsUrl::kvp($this->root().'/wmts.cgi', ['SERVICE' => 'WMTS', 'REQUEST' => 'GetCapabilities', 'VERSION' => '1.0.0']);

        return $this->pending(
            base: NasaURL::GIBS,
            path: $path,
            call_name: 'stargazer.gibs.wmts.capabilities',
            hydrator: fn (string $xml): WmtsCapabilities => WmtsCapabilities::fromXml($xml, $path),
            payload: NasaPayload::XML,
        )->timeout(self::CAPABILITIES_TIMEOUT);
    }

    /**
     * One tile. Answers the Http Response (body() is the image or vector tile);
     * an exception report in its place throws StargazerException.
     *
     * @param  string|null  $time  ISO 8601 date or date-time; null for a layer without time, or for its default.
     */
    public function tile(
        string $layer,
        string $tileMatrixSet,
        int $zoom,
        int $row,
        int $col,
        GibsTileFormat $format = GibsTileFormat::JPEG,
        ?string $time = null,
        GibsRequestStyle $style = GibsRequestStyle::REST,
        string $layerStyle = 'default',
    ): PendingNasaRequest {
        if ($style === GibsRequestStyle::REST) {
            $segments = [$layer, $layerStyle, ...(is_null($time) ? [] : [$time]), $tileMatrixSet, $zoom, $row, $col.'.'.$format->extension()];
            $path = $this->root().'/'.implode('/', array_map(fn (string|int $s): string => rawurlencode((string) $s), $segments));
        } else {
            $path = GibsUrl::kvp($this->root().'/wmts.cgi', [
                'SERVICE' => 'WMTS', 'REQUEST' => 'GetTile', 'VERSION' => '1.0.0',
                'LAYER' => $layer, 'STYLE' => $layerStyle, 'TILEMATRIXSET' => $tileMatrixSet,
                'TILEMATRIX' => $zoom, 'TILEROW' => $row, 'TILECOL' => $col, 'FORMAT' => $format->value,
            ] + (is_null($time) ? [] : ['TIME' => $time]));
        }

        return $this->pending(
            base: NasaURL::GIBS,
            path: $path,
            call_name: 'stargazer.gibs.wmts.tile',
            hydrator: fn (Response $response): Response => GibsXml::image($response, $path),
            payload: NasaPayload::BYTES,
        );
    }

    /** One vector tile, decoded. Answers GibsVectorTile. */
    public function vectorTile(string $layer, string $tileMatrixSet, int $zoom, int $row, int $col, ?string $time = null, GibsRequestStyle $style = GibsRequestStyle::REST, string $layerStyle = 'default'): PendingNasaRequest
    {
        $tile = $this->tile($layer, $tileMatrixSet, $zoom, $row, $col, GibsTileFormat::MVT, $time, $style, $layerStyle);

        return $this->pending(
            base: NasaURL::GIBS,
            path: substr($tile->url(), strlen(NasaURL::GIBS->value) + 1),
            call_name: 'stargazer.gibs.wmts.vectorTile',
            hydrator: fn (Response $response): GibsVectorTile => GibsVectorTile::fromBytes(GibsXml::image($response, $tile->url())->body()),
            payload: NasaPayload::BYTES,
        );
    }

    /**
     * Where and when $layer has data. Answers WmtsDomains.
     *
     * @param  GibsBox|null  $box  Limit to this area (the set's CRS units); null for everywhere.
     * @param  string|null  $from  From this instant on; with $to, from it to that one.
     * @param  string|null  $to  Up to this instant. Neither: all time.
     */
    public function domains(
        string $layer,
        string $tileMatrixSet,
        ?GibsBox $box = null,
        ?string $from = null,
        ?string $to = null,
        GibsRequestStyle $style = GibsRequestStyle::REST,
        string $layerStyle = 'default',
    ): PendingNasaRequest {
        $time = match (true) {
            is_null($from) && is_null($to) => null,
            is_null($to) => $from,
            default => ($from ?? '').'/'.$to,
        };
        if ($style === GibsRequestStyle::REST) {
            $segments = [$layer, $layerStyle, $tileMatrixSet, $box?->toString() ?? 'all', (is_null($time) ? 'all' : str_replace('/', '--', $time)).'.xml'];
            $path = $this->root().'/1.0.0/'.implode('/', array_map(rawurlencode(...), $segments));
        } else {
            $path = GibsUrl::kvp($this->root().'/wmts.cgi', [
                'SERVICE' => 'WMTS', 'REQUEST' => 'DescribeDomains', 'VERSION' => '1.0.0',
                'LAYER' => $layer, 'TILEMATRIXSET' => $tileMatrixSet, 'DOMAINS' => 'bbox,time',
            ] + (is_null($box) ? [] : ['BBOX' => $box->toString()]) + (is_null($time) ? [] : ['TIME' => $time]));
        }

        return $this->pending(
            base: NasaURL::GIBS,
            path: $path,
            call_name: 'stargazer.gibs.wmts.domains',
            hydrator: fn (string $xml): WmtsDomains => WmtsDomains::fromXml($xml, $path),
            payload: NasaPayload::XML,
        );
    }

    private function root(): string
    {
        return 'wmts/'.$this->projection->value.'/'.$this->set->value;
    }
}
