<?php

namespace ProjectSaturnStudios\Stargazer\NeoWs\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;
use Voyager\NutsAndBolts\Collection;

final readonly class NearEarthObject implements HydratesFromArray
{
    use HydratesNasaData;

    /**
     * @param  Collection<int, CloseApproach>  $close_approach_data
     */
    public function __construct(
        public string $id,
        public ?string $neo_reference_id,
        public ?string $name,
        public ?string $designation,
        public ?string $nasa_jpl_url,
        public ?float $absolute_magnitude_h,
        public ?EstimatedDiameter $estimated_diameter,
        public ?bool $is_potentially_hazardous_asteroid,
        public Collection $close_approach_data,
        public ?OrbitalData $orbital_data,
        public ?bool $is_sentry_object,
        public ?NeoLinks $links,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            id: self::text($data, 'id'),
            neo_reference_id: self::optionalText($data, 'neo_reference_id'),
            name: self::optionalText($data, 'name'),
            designation: self::optionalText($data, 'designation'),
            nasa_jpl_url: self::optionalText($data, 'nasa_jpl_url'),
            absolute_magnitude_h: self::optionalFloat($data, 'absolute_magnitude_h'),
            estimated_diameter: isset($data['estimated_diameter']) ? EstimatedDiameter::fromArray((array) $data['estimated_diameter']) : null,
            is_potentially_hazardous_asteroid: self::optionalBool($data, 'is_potentially_hazardous_asteroid'),
            close_approach_data: self::collectionOf($data['close_approach_data'] ?? [], CloseApproach::class),
            orbital_data: isset($data['orbital_data']) ? OrbitalData::fromArray((array) $data['orbital_data']) : null,
            is_sentry_object: self::optionalBool($data, 'is_sentry_object'),
            links: isset($data['links']) ? NeoLinks::fromArray((array) $data['links']) : null,
        );
    }
}
