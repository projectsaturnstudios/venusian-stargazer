<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Wms;

use ProjectSaturnStudios\Stargazer\Enums\NasaPayload;
use ProjectSaturnStudios\Stargazer\Enums\NasaURL;
use ProjectSaturnStudios\Stargazer\GIBS\DataObjects\GibsBox;
use ProjectSaturnStudios\Stargazer\GIBS\Enums\GibsImagerySet;
use ProjectSaturnStudios\Stargazer\GIBS\Enums\GibsMapFormat;
use ProjectSaturnStudios\Stargazer\GIBS\Enums\GibsProjection;
use ProjectSaturnStudios\Stargazer\GIBS\Enums\WmsVersion;
use ProjectSaturnStudios\Stargazer\GIBS\Support\GibsUrl;
use ProjectSaturnStudios\Stargazer\GIBS\Support\GibsXml;
use ProjectSaturnStudios\Stargazer\GIBS\Wms\DataObjects\WmsCapabilities;
use ProjectSaturnStudios\Stargazer\NasaApiService;
use ProjectSaturnStudios\Stargazer\NasaClient;
use ProjectSaturnStudios\Stargazer\PendingNasaRequest;
use Voyager\Http\Client\Response;

/**
 * GIBS WMS for one projection and imagery set: GetCapabilities, GetMap (any
 * box, any size, several layers stacked) and GetLegendGraphic. GIBS turns
 * GetFeatureInfo off on its side.
 */
class GibsWms extends NasaApiService
{
    public function __construct(
        NasaClient $client,
        public readonly GibsProjection $projection = GibsProjection::EPSG4326,
        public readonly GibsImagerySet $set = GibsImagerySet::BEST,
    ) {
        parent::__construct($client);
    }

    /** Answers WmsCapabilities. */
    public function capabilities(WmsVersion $version = WmsVersion::V1_3_0): PendingNasaRequest
    {
        $path = GibsUrl::kvp($this->root(), ['SERVICE' => 'WMS', 'REQUEST' => 'GetCapabilities', 'VERSION' => $version->value]);

        return $this->pending(
            base: NasaURL::GIBS,
            path: $path,
            call_name: 'stargazer.gibs.wms.capabilities',
            hydrator: fn (string $xml): WmsCapabilities => WmsCapabilities::fromXml($xml, $path),
            payload: NasaPayload::XML,
        )->timeout(120.0);
    }

    /**
     * A map of $box at $width × $height pixels, the layers drawn in the order
     * given (first at the bottom). Answers the Http Response; body() is the image.
     *
     * @param  string|list<string>  $layers
     * @param  GibsBox  $box  In the projection's units, x east, y north, whatever the version.
     * @param  list<string>  $styles  One per layer; none for each layer's default.
     * @param  bool  $transparent  Leave no-data pixels clear (PNG) so maps can be stacked.
     */
    public function map(
        string|array $layers,
        GibsBox $box,
        int $width,
        int $height,
        GibsMapFormat $format = GibsMapFormat::JPEG,
        ?string $time = null,
        WmsVersion $version = WmsVersion::V1_3_0,
        bool $transparent = false,
        array $styles = [],
    ): PendingNasaRequest {
        $latitudeFirst = $version === WmsVersion::V1_3_0 && $this->projection === GibsProjection::EPSG4326;
        $path = GibsUrl::kvp($this->root(), [
            'SERVICE' => 'WMS', 'REQUEST' => 'GetMap', 'VERSION' => $version->value,
            'LAYERS' => implode(',', (array) $layers), 'STYLES' => implode(',', $styles),
            ($version === WmsVersion::V1_3_0 ? 'CRS' : 'SRS') => $this->projection->crs(),
            'BBOX' => $latitudeFirst ? $box->toLatitudeFirstString() : $box->toString(),
            'WIDTH' => $width, 'HEIGHT' => $height, 'FORMAT' => $format->value,
        ] + ($transparent ? ['TRANSPARENT' => 'TRUE'] : []) + (is_null($time) ? [] : ['TIME' => $time]));

        return $this->image($path, 'stargazer.gibs.wms.map');
    }

    /** The legend image of a layer whose style lists a GetLegendGraphic legend. Answers the Http Response. */
    public function legendGraphic(string $layer, string $style = 'default', WmsVersion $version = WmsVersion::V1_3_0): PendingNasaRequest
    {
        $path = GibsUrl::kvp($this->root(), [
            'SERVICE' => 'WMS', 'REQUEST' => 'GetLegendGraphic', 'VERSION' => $version->value,
            'LAYER' => $layer, 'STYLE' => $style, 'FORMAT' => 'image/png', 'SLD_VERSION' => '1.1.0',
        ]);

        return $this->image($path, 'stargazer.gibs.wms.legendGraphic');
    }

    private function image(string $path, string $call): PendingNasaRequest
    {
        return $this->pending(
            base: NasaURL::GIBS,
            path: $path,
            call_name: $call,
            hydrator: fn (Response $response): Response => GibsXml::image($response, $path),
            payload: NasaPayload::BYTES,
        );
    }

    private function root(): string
    {
        return 'wms/'.$this->projection->value.'/'.$this->set->value.'/wms.cgi';
    }
}
