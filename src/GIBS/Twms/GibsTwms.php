<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Twms;

use ProjectSaturnStudios\Stargazer\Enums\NasaPayload;
use ProjectSaturnStudios\Stargazer\Enums\NasaURL;
use ProjectSaturnStudios\Stargazer\GIBS\DataObjects\GibsBox;
use ProjectSaturnStudios\Stargazer\GIBS\Enums\GibsImagerySet;
use ProjectSaturnStudios\Stargazer\GIBS\Enums\GibsProjection;
use ProjectSaturnStudios\Stargazer\GIBS\Enums\GibsTileFormat;
use ProjectSaturnStudios\Stargazer\GIBS\Support\GibsUrl;
use ProjectSaturnStudios\Stargazer\GIBS\Support\GibsXml;
use ProjectSaturnStudios\Stargazer\GIBS\Twms\DataObjects\TwmsTilePattern;
use ProjectSaturnStudios\Stargazer\GIBS\Twms\DataObjects\TwmsTileService;
use ProjectSaturnStudios\Stargazer\GIBS\Wms\DataObjects\WmsCapabilities;
use ProjectSaturnStudios\Stargazer\NasaApiService;
use ProjectSaturnStudios\Stargazer\NasaClient;
use ProjectSaturnStudios\Stargazer\PendingNasaRequest;
use Closure;
use Voyager\Http\Client\Response;

/**
 * GIBS Tiled WMS for one projection and imagery set: WMS-shaped GetMap requests
 * that only answer the fixed tiles GetTileService lists.
 */
class GibsTwms extends NasaApiService
{
    public function __construct(
        NasaClient $client,
        public readonly GibsProjection $projection = GibsProjection::EPSG4326,
        public readonly GibsImagerySet $set = GibsImagerySet::BEST,
    ) {
        parent::__construct($client);
    }

    /** Answers WmsCapabilities in the 1.1.1 shape TWMS writes. */
    public function capabilities(): PendingNasaRequest
    {
        return $this->xml(['request' => 'GetCapabilities'], 'stargazer.gibs.twms.capabilities', WmsCapabilities::fromXml(...));
    }

    /** Answers TwmsTileService: every layer's tile grid. */
    public function tileService(): PendingNasaRequest
    {
        return $this->xml(['request' => 'GetTileService'], 'stargazer.gibs.twms.tileService', TwmsTileService::fromXml(...));
    }

    /** One tile by its box; it must be one GetTileService lists. Answers the Http Response. */
    public function map(string $layer, GibsBox $box, int $width = 512, int $height = 512, GibsTileFormat $format = GibsTileFormat::JPEG, ?string $time = null): PendingNasaRequest
    {
        return $this->image(GibsUrl::kvp($this->root(), [
            'request' => 'GetMap', 'layers' => $layer, 'srs' => $this->projection->crs(), 'format' => $format->value, 'styles' => '',
        ] + (is_null($time) ? [] : ['time' => $time]) + ['width' => $width, 'height' => $height, 'bbox' => $box->toString()]));
    }

    /** One tile from its TilePattern, with $time put in when the pattern takes one. Answers the Http Response. */
    public function tile(TwmsTilePattern $pattern, ?string $time = null): PendingNasaRequest
    {
        return $this->image($this->root().'?'.$pattern->query($time));
    }

    private function image(string $path): PendingNasaRequest
    {
        return $this->pending(
            base: NasaURL::GIBS,
            path: $path,
            call_name: 'stargazer.gibs.twms.map',
            hydrator: fn (Response $response): Response => GibsXml::image($response, $path),
            payload: NasaPayload::BYTES,
        );
    }

    private function xml(array $query, string $call, Closure $read): PendingNasaRequest
    {
        $path = GibsUrl::kvp($this->root(), $query);

        return $this->pending(
            base: NasaURL::GIBS,
            path: $path,
            call_name: $call,
            hydrator: fn (string $xml) => $read($xml, $path),
            payload: NasaPayload::XML,
        )->timeout(120.0);
    }

    private function root(): string
    {
        return 'twms/'.$this->projection->value.'/'.$this->set->value.'/twms.cgi';
    }
}
