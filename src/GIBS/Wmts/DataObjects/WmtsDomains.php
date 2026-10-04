<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Wmts\DataObjects;

use ProjectSaturnStudios\Stargazer\GIBS\DataObjects\GibsBox;
use ProjectSaturnStudios\Stargazer\GIBS\DataObjects\GibsTimePeriod;
use ProjectSaturnStudios\Stargazer\GIBS\Support\GibsXml;

/** A DescribeDomains answer: where (bbox) and when (time) a layer has data within what was asked. */
final readonly class WmtsDomains
{
    /**
     * @param  list<GibsTimePeriod>  $periods
     */
    public function __construct(
        public ?GibsBox $box,
        public ?string $crs,
        public array $periods,
        public ?int $size,
    ) {}

    public static function fromXml(string $xml, string $url = 'DescribeDomains'): self
    {
        $root = GibsXml::load($xml, $url);
        $box = null;
        $crs = null;
        $periods = [];
        $size = null;

        $bounds = $root->xpath('//*[local-name()="SpaceDomain"]/*[local-name()="BoundingBox"]')[0] ?? null;
        if (! is_null($bounds)) {
            $box = new GibsBox((float) $bounds['minx'], (float) $bounds['miny'], (float) $bounds['maxx'], (float) $bounds['maxy']);
            $crs = ((string) $bounds['crs']) ?: null;
        }
        foreach ($root->xpath('//*[local-name()="DimensionDomain"]') as $dimension) {
            if (strcasecmp((string) $dimension->children(GibsXml::OWS)->Identifier, 'time') !== 0) {
                continue;
            }
            foreach (explode(',', (string) $dimension->Domain) as $entry) {
                if (trim($entry) !== '') {
                    $periods[] = GibsTimePeriod::parse($entry);
                }
            }
            $size = isset($dimension->Size) ? (int) $dimension->Size : null;
        }

        return new self($box, $crs, $periods, $size);
    }
}
