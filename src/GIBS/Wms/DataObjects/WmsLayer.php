<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Wms\DataObjects;

use ProjectSaturnStudios\Stargazer\GIBS\DataObjects\GibsBox;
use ProjectSaturnStudios\Stargazer\GIBS\DataObjects\GibsTimeDimension;
use ProjectSaturnStudios\Stargazer\GIBS\Support\GibsXml;
use SimpleXMLElement;

/** A named layer of a WMS (or TWMS) capabilities document. A group is a layer that holds others. */
final readonly class WmsLayer
{
    /**
     * @param  list<string>  $crs  The CRS (1.3.0) or SRS (1.1.1) it is served in.
     * @param  list<WmsStyle>  $styles
     * @param  list<string>  $children  Names of the layers it groups.
     */
    public function __construct(
        public string $name,
        public string $title,
        public ?string $abstract,
        public array $crs,
        public ?GibsBox $wgs84Box,
        public ?GibsTimeDimension $time,
        public array $styles,
        public ?string $group,
        public array $children,
        public bool $queryable,
        public bool $opaque,
    ) {}

    public static function fromXml(SimpleXMLElement $layer, ?string $group = null): self
    {
        $time = null;
        foreach ([...GibsXml::all($layer, 'Dimension'), ...GibsXml::all($layer, 'Extent')] as $dimension) {
            // 1.3.0 keeps the values in <Dimension>; 1.1.1 in <Extent>, its <Dimension> empty.
            if (strcasecmp((string) $dimension['name'], 'time') === 0 && trim((string) $dimension) !== '') {
                $time = GibsTimeDimension::fromWms($dimension);
            }
        }

        return new self(
            name: GibsXml::text($layer, 'Name') ?? '',
            title: GibsXml::text($layer, 'Title') ?? '',
            abstract: GibsXml::text($layer, 'Abstract'),
            crs: array_map(fn (SimpleXMLElement $c): string => trim((string) $c), [...GibsXml::all($layer, 'CRS'), ...GibsXml::all($layer, 'SRS')]),
            wgs84Box: self::box($layer),
            time: $time,
            styles: array_map(WmsStyle::fromXml(...), GibsXml::all($layer, 'Style')),
            group: $group,
            children: array_values(array_filter(array_map(fn (SimpleXMLElement $child): ?string => GibsXml::text($child, 'Name'), GibsXml::all($layer, 'Layer')))),
            queryable: (string) $layer['queryable'] === '1',
            opaque: (string) $layer['opaque'] === '1',
        );
    }

    /** EX_GeographicBoundingBox (1.3.0) or LatLonBoundingBox (1.1.1, TWMS). */
    private static function box(SimpleXMLElement $layer): ?GibsBox
    {
        if ($geographic = GibsXml::first($layer, 'EX_GeographicBoundingBox')) {
            return new GibsBox(
                (float) GibsXml::text($geographic, 'westBoundLongitude'),
                (float) GibsXml::text($geographic, 'southBoundLatitude'),
                (float) GibsXml::text($geographic, 'eastBoundLongitude'),
                (float) GibsXml::text($geographic, 'northBoundLatitude'),
            );
        }
        if ($box = GibsXml::first($layer, 'LatLonBoundingBox')) {
            return new GibsBox((float) $box['minx'], (float) $box['miny'], (float) $box['maxx'], (float) $box['maxy']);
        }

        return null;
    }

    public function isGroup(): bool
    {
        return $this->children !== [];
    }

    /** The legend of $style (the first style when null), or null. */
    public function legend(?string $style = null): ?WmsLegend
    {
        foreach ($this->styles as $candidate) {
            if (is_null($style) || $candidate->name === $style) {
                return $candidate->legends[0] ?? null;
            }
        }

        return null;
    }
}
