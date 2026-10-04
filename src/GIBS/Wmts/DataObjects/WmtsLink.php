<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Wmts\DataObjects;

use ProjectSaturnStudios\Stargazer\GIBS\Support\GibsXml;
use SimpleXMLElement;

/** An ows:Metadata link: a colour map, layer metadata or vector style for the layer, named by its role. */
final readonly class WmtsLink
{
    public function __construct(
        public string $role,
        public string $href,
        public string $title,
    ) {}

    public static function fromXml(SimpleXMLElement $metadata): self
    {
        $xlink = $metadata->attributes(GibsXml::XLINK);

        return new self((string) $xlink['role'], (string) $xlink['href'], (string) $xlink['title']);
    }

    /** The role past GIBS's prefix: 'colormap/1.3', 'layer/1.0', 'mapbox-gl-style/1.0', or the bare kinds ending in '/'. */
    public function kind(): string
    {
        return str_replace('http://earthdata.nasa.gov/gibs/metadata-type/', '', $this->role);
    }
}
