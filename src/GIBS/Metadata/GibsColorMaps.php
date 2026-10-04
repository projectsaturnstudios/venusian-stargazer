<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Metadata;

use ProjectSaturnStudios\Stargazer\Exceptions\StargazerException;
use ProjectSaturnStudios\Stargazer\GIBS\Support\GibsXml;

/** A colour map file: v1.0 holds one map, v1.3 a no-data map and a data map. */
final readonly class GibsColorMaps
{
    /**
     * @param  list<GibsColorMap>  $maps
     */
    public function __construct(public array $maps) {}

    /** @throws StargazerException */
    public static function fromXml(string $xml, string $url = 'colour map'): self
    {
        $root = GibsXml::load($xml, $url);

        return match ($root->getName()) {
            'ColorMap' => new self([GibsColorMap::fromXml($root)]),
            'ColorMaps' => new self(array_map(GibsColorMap::fromXml(...), GibsXml::all($root, 'ColorMap'))),
            default => throw StargazerException::invalidXml($url, "its root is <{$root->getName()}>, not a colour map"),
        };
    }

    /** The map with data entries in it: the one not every entry of which is no-data. */
    public function data(): ?GibsColorMap
    {
        foreach ($this->maps as $map) {
            foreach ($map->entries as $entry) {
                if (! $entry->noData) {
                    return $map;
                }
            }
        }

        return null;
    }
}
