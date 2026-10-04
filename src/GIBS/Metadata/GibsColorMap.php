<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Metadata;

use ProjectSaturnStudios\Stargazer\GIBS\Support\GibsXml;
use SimpleXMLElement;

/** One colour map: entries in order and, for v1.3, how its legend reads. */
final readonly class GibsColorMap
{
    /**
     * @param  list<GibsColorMapEntry>  $entries
     * @param  list<GibsLegendEntry>  $legend
     */
    public function __construct(
        public ?string $title,
        public ?string $units,
        public array $entries,
        public ?string $legendType,
        public ?string $minLabel,
        public ?string $maxLabel,
        public array $legend,
    ) {}

    public static function fromXml(SimpleXMLElement $map): self
    {
        // v1.3 wraps entries in <Entries>; v1.0 puts them straight in the map.
        $entries = GibsXml::first($map, 'Entries') ?? $map;
        $legend = GibsXml::first($map, 'Legend');

        return new self(
            title: isset($map['title']) ? (string) $map['title'] : null,
            units: isset($map['units']) ? (string) $map['units'] : null,
            entries: array_map(GibsColorMapEntry::fromXml(...), GibsXml::all($entries, 'ColorMapEntry')),
            legendType: is_null($legend) ? null : (string) $legend['type'],
            minLabel: isset($legend['minLabel']) ? (string) $legend['minLabel'] : null,
            maxLabel: isset($legend['maxLabel']) ? (string) $legend['maxLabel'] : null,
            legend: is_null($legend) ? [] : array_map(GibsLegendEntry::fromXml(...), GibsXml::all($legend, 'LegendEntry')),
        );
    }

    /** The entry whose data range holds $value; null when none does. */
    public function entryFor(float $value): ?GibsColorMapEntry
    {
        foreach ($this->entries as $entry) {
            if ($entry->covers($value)) {
                return $entry;
            }
        }

        return null;
    }
}
