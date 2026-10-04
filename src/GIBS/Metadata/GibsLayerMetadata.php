<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Metadata;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;

/**
 * A layer's metadata file (layer-metadata/v1.0): what it measures, where it
 * comes from, and, for a vector layer, its features' properties. GIBS's vector
 * metadata files hold only id and mvt_properties, so they read as this too.
 */
final readonly class GibsLayerMetadata implements HydratesFromArray
{
    use HydratesNasaData;

    /**
     * @param  list<string>  $daynight
     * @param  list<GibsConceptId>  $conceptIds
     * @param  list<string>  $orbitTracks
     * @param  list<string>  $orbitDirection
     * @param  list<GibsMvtProperty>  $mvtProperties
     */
    public function __construct(
        public ?string $id,
        public ?string $title,
        public ?string $subtitle,
        public ?bool $ongoing,
        public ?string $measurement,
        public ?int $retentionPeriod,
        public ?string $layerPeriod,
        public array $daynight,
        public array $conceptIds,
        public array $orbitTracks,
        public array $orbitDirection,
        public array $mvtProperties,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            id: self::optionalText($data, 'id'),
            title: self::optionalText($data, 'title'),
            subtitle: self::optionalText($data, 'subtitle'),
            ongoing: self::optionalBool($data, 'ongoing'),
            measurement: self::optionalText($data, 'measurement'),
            retentionPeriod: self::optionalInt($data, 'retentionPeriod'),
            layerPeriod: self::optionalText($data, 'layerPeriod'),
            daynight: self::stringList($data['daynight'] ?? null)->all(),
            conceptIds: self::collectionOf($data['conceptIds'] ?? null, GibsConceptId::class)->all(),
            orbitTracks: self::stringList($data['orbitTracks'] ?? null)->all(),
            orbitDirection: self::stringList($data['orbitDirection'] ?? null)->all(),
            mvtProperties: self::collectionOf($data['mvt_properties'] ?? null, GibsMvtProperty::class)->all(),
        );
    }

    /** The property described as $identifier, or null. */
    public function property(string $identifier): ?GibsMvtProperty
    {
        foreach ($this->mvtProperties as $property) {
            if ($property->identifier === $identifier) {
                return $property;
            }
        }

        return null;
    }
}
