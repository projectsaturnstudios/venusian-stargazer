<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Wms\DataObjects;

use ProjectSaturnStudios\Stargazer\GIBS\Support\GibsXml;
use SimpleXMLElement;

/** A style's LegendURL: an image, a /legends file or a GetLegendGraphic request. */
final readonly class WmsLegend
{
    public function __construct(
        public string $format,
        public string $href,
        public ?int $width,
        public ?int $height,
    ) {}

    public static function fromXml(SimpleXMLElement $legend): self
    {
        $resource = GibsXml::first($legend, 'OnlineResource');

        return new self(
            format: GibsXml::text($legend, 'Format') ?? '',
            href: is_null($resource) ? '' : GibsXml::href($resource),
            width: isset($legend['width']) ? (int) $legend['width'] : null,
            height: isset($legend['height']) ? (int) $legend['height'] : null,
        );
    }
}
