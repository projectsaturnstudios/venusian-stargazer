<?php

namespace ProjectSaturnStudios\Stargazer\GIBS;

use ProjectSaturnStudios\Stargazer\Enums\NasaPayload;
use ProjectSaturnStudios\Stargazer\Enums\NasaURL;
use ProjectSaturnStudios\Stargazer\GIBS\Enums\GibsColorMapVersion;
use ProjectSaturnStudios\Stargazer\GIBS\Enums\GibsImagerySet;
use ProjectSaturnStudios\Stargazer\GIBS\Enums\GibsLegendFormat;
use ProjectSaturnStudios\Stargazer\GIBS\Enums\GibsLegendOrientation;
use ProjectSaturnStudios\Stargazer\GIBS\Enums\GibsProjection;
use ProjectSaturnStudios\Stargazer\GIBS\Metadata\GibsColorMaps;
use ProjectSaturnStudios\Stargazer\GIBS\Metadata\GibsLayerMetadata;
use ProjectSaturnStudios\Stargazer\GIBS\Support\GibsXml;
use ProjectSaturnStudios\Stargazer\GIBS\Twms\GibsTwms;
use ProjectSaturnStudios\Stargazer\GIBS\Vector\GibsVectorStyle;
use ProjectSaturnStudios\Stargazer\GIBS\Wms\GibsWms;
use ProjectSaturnStudios\Stargazer\GIBS\Wmts\GibsWmts;
use ProjectSaturnStudios\Stargazer\NasaApiService;
use ProjectSaturnStudios\Stargazer\PendingNasaRequest;
use Voyager\Http\Client\Response;

/**
 * NASA's Global Imagery Browse Services: satellite imagery and data layers
 * as tiles (WMTS, TWMS), maps (WMS) and the files that describe them. No API
 * key. Images and tiles answer the Http Response; everything else answers
 * data objects.
 */
class GibsAPIService extends NasaApiService
{
    public function wmts(GibsProjection $projection = GibsProjection::EPSG4326, GibsImagerySet $set = GibsImagerySet::BEST): GibsWmts
    {
        return new GibsWmts($this->client, $projection, $set);
    }

    public function wms(GibsProjection $projection = GibsProjection::EPSG4326, GibsImagerySet $set = GibsImagerySet::BEST): GibsWms
    {
        return new GibsWms($this->client, $projection, $set);
    }

    public function twms(GibsProjection $projection = GibsProjection::EPSG4326, GibsImagerySet $set = GibsImagerySet::BEST): GibsTwms
    {
        return new GibsTwms($this->client, $projection, $set);
    }

    /**
     * A layer's colour map: which colour stands for which data value. $id is
     * the file stem a WMTS layer links to (WmtsLayer::linkId('colormap/1.3')).
     * Answers GibsColorMaps.
     */
    public function colormap(string $id, GibsColorMapVersion $version = GibsColorMapVersion::V1_3): PendingNasaRequest
    {
        $path = 'colormaps/'.$version->value.'/'.rawurlencode($id).'.xml';

        return $this->pending(
            base: NasaURL::GIBS,
            path: $path,
            call_name: 'stargazer.gibs.colormap',
            hydrator: fn (string $xml): GibsColorMaps => GibsColorMaps::fromXml($xml, $path),
            payload: NasaPayload::XML,
        );
    }

    /** A colour-mapped layer's legend image. Answers the Http Response; body() is the SVG or PNG. */
    public function legend(string $id, GibsLegendOrientation $orientation = GibsLegendOrientation::HORIZONTAL, GibsLegendFormat $format = GibsLegendFormat::PNG): PendingNasaRequest
    {
        $path = 'legends/'.rawurlencode($id).'_'.$orientation->value.'.'.$format->value;

        return $this->pending(
            base: NasaURL::GIBS,
            path: $path,
            call_name: 'stargazer.gibs.legend',
            hydrator: fn (Response $response): Response => GibsXml::image($response, $path),
            payload: NasaPayload::BYTES,
        );
    }

    /** A layer's metadata (WmtsLayer::linkId('layer/1.0') for raster layers). Answers GibsLayerMetadata. */
    public function layerMetadata(string $id): PendingNasaRequest
    {
        return $this->pending(NasaURL::GIBS, 'layer-metadata/v1.0/'.rawurlencode($id).'.json', 'stargazer.gibs.layerMetadata', GibsLayerMetadata::class);
    }

    /** A vector layer's feature properties (its 'layer/1.0' link's stem). Answers GibsLayerMetadata with mvtProperties. */
    public function vectorMetadata(string $id): PendingNasaRequest
    {
        return $this->pending(NasaURL::GIBS, 'vector-metadata/v1.0/'.rawurlencode($id).'.json', 'stargazer.gibs.vectorMetadata', GibsLayerMetadata::class);
    }

    /** A vector layer's style (its 'mapbox-gl-style/1.0' link's stem). Answers GibsVectorStyle. */
    public function vectorStyle(string $id): PendingNasaRequest
    {
        return $this->pending(NasaURL::GIBS, 'vector-styles/v1.0/'.rawurlencode($id).'.json', 'stargazer.gibs.vectorStyle', GibsVectorStyle::class);
    }
}
