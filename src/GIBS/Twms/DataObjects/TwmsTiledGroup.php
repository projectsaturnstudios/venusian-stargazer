<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Twms\DataObjects;

use ProjectSaturnStudios\Stargazer\GIBS\DataObjects\GibsBox;
use ProjectSaturnStudios\Stargazer\GIBS\Support\GibsXml;
use SimpleXMLElement;

/** A TiledGroup: one layer's fixed tile grid, as GetMap query patterns. */
final readonly class TwmsTiledGroup
{
    /**
     * @param  list<TwmsTilePattern>  $patterns
     */
    public function __construct(
        public string $name,
        public string $title,
        public ?string $abstract,
        public string $projection,
        public int $pad,
        public int $bands,
        public ?GibsBox $box,
        public string $key,
        public array $patterns,
    ) {}

    public static function fromXml(SimpleXMLElement $group): self
    {
        $key = GibsXml::text($group, 'Key') ?? '${time}';
        $box = GibsXml::first($group, 'LatLonBoundingBox');

        return new self(
            name: GibsXml::text($group, 'Name') ?? '',
            title: GibsXml::text($group, 'Title') ?? '',
            abstract: GibsXml::text($group, 'Abstract'),
            projection: GibsXml::text($group, 'Projection') ?? '',
            pad: (int) GibsXml::text($group, 'Pad'),
            bands: (int) GibsXml::text($group, 'Bands'),
            box: is_null($box) ? null : new GibsBox((float) $box['minx'], (float) $box['miny'], (float) $box['maxx'], (float) $box['maxy']),
            key: $key,
            patterns: array_map(fn (SimpleXMLElement $p): TwmsTilePattern => TwmsTilePattern::fromText((string) $p, $key), GibsXml::all($group, 'TilePattern')),
        );
    }

    /** The layer the patterns ask for: their layers= value. */
    public function layer(): string
    {
        $pattern = $this->patterns[0] ?? null;
        parse_str($pattern?->timeless ?? $pattern?->timed ?? '', $query);

        return (string) ($query['layers'] ?? '');
    }
}
