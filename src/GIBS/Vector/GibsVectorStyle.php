<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Vector;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;

/** A vector style (Mapbox GL style 8): its sources' tile URLs and its layers, drawn in order. */
final readonly class GibsVectorStyle implements HydratesFromArray
{
    /**
     * @param  array<string, list<string>>  $sources  Source name => its tile URL templates.
     * @param  list<GibsStyleLayer>  $layers
     */
    public function __construct(
        public int $version,
        public string $name,
        public array $sources,
        public array $layers,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            version: (int) ($data['version'] ?? 8),
            name: (string) ($data['name'] ?? ''),
            sources: array_map(fn (array $source): array => array_values(array_map('strval', $source['tiles'] ?? [])), (array) ($data['sources'] ?? [])),
            layers: array_map(GibsStyleLayer::fromArray(...), array_values((array) ($data['layers'] ?? []))),
        );
    }

    /** @return list<GibsStyleLayer> The layers drawing from the tile layer named $sourceLayer. */
    public function layersFor(string $sourceLayer): array
    {
        return array_values(array_filter($this->layers, fn (GibsStyleLayer $layer): bool => $layer->sourceLayer === $sourceLayer));
    }
}
