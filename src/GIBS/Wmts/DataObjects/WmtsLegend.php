<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Wmts\DataObjects;

use ProjectSaturnStudios\Stargazer\GIBS\Support\GibsXml;
use SimpleXMLElement;

/** A LegendURL: a legend image for one style, horizontal or vertical. */
final readonly class WmtsLegend
{
    public function __construct(
        public string $format,
        public string $href,
        public ?string $orientation,
        public ?int $width,
        public ?int $height,
    ) {}

    public static function fromXml(SimpleXMLElement $legend): self
    {
        $role = (string) $legend->attributes(GibsXml::XLINK)['role'];

        return new self(
            format: (string) $legend['format'],
            href: GibsXml::href($legend),
            orientation: $role === '' ? null : basename($role),
            width: isset($legend['width']) ? (int) $legend['width'] : null,
            height: isset($legend['height']) ? (int) $legend['height'] : null,
        );
    }
}
