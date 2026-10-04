<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Wmts\DataObjects;

use ProjectSaturnStudios\Stargazer\GIBS\DataObjects\GibsBox;
use ProjectSaturnStudios\Stargazer\GIBS\DataObjects\GibsTimeDimension;
use ProjectSaturnStudios\Stargazer\GIBS\Enums\GibsTileFormat;
use ProjectSaturnStudios\Stargazer\GIBS\Support\GibsXml;
use SimpleXMLElement;

/** One layer of a WMTS capabilities document. */
final readonly class WmtsLayer
{
    /**
     * @param  list<string>  $formats  MIME types its tiles come in.
     * @param  list<string>  $tileMatrixSets  Identifiers of the sets it is tiled in.
     * @param  list<WmtsResource>  $resources
     * @param  list<WmtsStyle>  $styles
     * @param  list<WmtsLink>  $links
     */
    public function __construct(
        public string $identifier,
        public string $title,
        public ?GibsBox $wgs84Box,
        public array $formats,
        public array $tileMatrixSets,
        public ?GibsTimeDimension $time,
        public array $resources,
        public array $styles,
        public array $links,
    ) {}

    public static function fromXml(SimpleXMLElement $layer): self
    {
        $ows = $layer->children(GibsXml::OWS);
        $box = null;
        if (isset($ows->WGS84BoundingBox)) {
            $corners = $ows->WGS84BoundingBox->children(GibsXml::OWS);
            [$minX, $minY] = GibsXml::corner((string) $corners->LowerCorner);
            [$maxX, $maxY] = GibsXml::corner((string) $corners->UpperCorner);
            $box = new GibsBox($minX, $minY, $maxX, $maxY);
        }

        $time = null;
        foreach ($layer->Dimension as $dimension) {
            if (strcasecmp((string) $dimension->children(GibsXml::OWS)->Identifier, 'Time') === 0) {
                $time = GibsTimeDimension::fromWmts($dimension);
            }
        }

        return new self(
            identifier: (string) $ows->Identifier,
            title: (string) $ows->Title,
            wgs84Box: $box,
            formats: array_map('strval', iterator_to_array($layer->Format, false)),
            tileMatrixSets: array_map(fn (SimpleXMLElement $link): string => (string) $link->TileMatrixSet, iterator_to_array($layer->TileMatrixSetLink, false)),
            time: $time,
            resources: array_map(WmtsResource::fromXml(...), iterator_to_array($layer->ResourceURL, false)),
            styles: array_map(WmtsStyle::fromXml(...), iterator_to_array($layer->Style, false)),
            links: array_map(WmtsLink::fromXml(...), iterator_to_array($ows->Metadata, false)),
        );
    }

    public function isVector(): bool
    {
        return in_array(GibsTileFormat::MVT->value, $this->formats, true);
    }

    /** The format its tiles come in: the first it lists that GIBS serves. */
    public function tileFormat(): ?GibsTileFormat
    {
        foreach ($this->formats as $format) {
            if ($tile = GibsTileFormat::tryFrom($format)) {
                return $tile;
            }
        }

        return null;
    }

    /** The style marked default, else the first. */
    public function defaultStyle(): ?WmtsStyle
    {
        foreach ($this->styles as $style) {
            if ($style->isDefault) {
                return $style;
            }
        }

        return $this->styles[0] ?? null;
    }

    /** The RESTful tile template; with $time, the one that takes a {Time}. */
    public function tileResource(bool $withTime): ?WmtsResource
    {
        foreach ($this->resources as $resource) {
            if ($resource->type === 'tile' && in_array('Time', $resource->placeholders(), true) === $withTime && ! str_contains($resource->template, '/default/default/')) {
                return $resource;
            }
        }

        return null;
    }

    /** The href of the first link of $kind ('colormap/1.3', 'colormap/1.0', 'layer/1.0', 'mapbox-gl-style/1.0'). */
    public function link(string $kind): ?string
    {
        foreach ($this->links as $link) {
            if ($link->kind() === $kind) {
                return $link->href;
            }
        }

        return null;
    }

    /** The file stem of a link of $kind: what GibsAPIService::colormap() and friends take. */
    public function linkId(string $kind): ?string
    {
        $href = $this->link($kind);

        return is_null($href) ? null : pathinfo(parse_url($href, PHP_URL_PATH), PATHINFO_FILENAME);
    }
}
