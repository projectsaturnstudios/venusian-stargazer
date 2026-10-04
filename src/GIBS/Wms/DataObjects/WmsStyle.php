<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Wms\DataObjects;

use ProjectSaturnStudios\Stargazer\GIBS\Support\GibsXml;
use SimpleXMLElement;

final readonly class WmsStyle
{
    /**
     * @param  list<WmsLegend>  $legends
     */
    public function __construct(
        public string $name,
        public string $title,
        public array $legends,
    ) {}

    public static function fromXml(SimpleXMLElement $style): self
    {
        return new self(
            GibsXml::text($style, 'Name') ?? '',
            GibsXml::text($style, 'Title') ?? '',
            array_map(WmsLegend::fromXml(...), GibsXml::all($style, 'LegendURL')),
        );
    }
}
