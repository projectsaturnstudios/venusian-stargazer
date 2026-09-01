<?php

namespace ProjectSaturnStudios\Stargazer\NeoWs\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;

final readonly class OrbitalData implements HydratesFromArray
{
    use HydratesNasaData;

    public function __construct(
        public ?string $orbit_id,
        public ?string $orbit_determination_date,
        public ?string $first_observation_date,
        public ?string $last_observation_date,
        public ?int $data_arc_in_days,
        public ?int $observations_used,
        public ?string $orbit_uncertainty,
        public ?string $minimum_orbit_intersection,
        public ?string $jupiter_tisserand_invariant,
        public ?string $epoch_osculation,
        public ?string $eccentricity,
        public ?string $semi_major_axis,
        public ?string $inclination,
        public ?string $ascending_node_longitude,
        public ?string $orbital_period,
        public ?string $perihelion_distance,
        public ?string $perihelion_argument,
        public ?string $aphelion_distance,
        public ?string $perihelion_time,
        public ?string $mean_anomaly,
        public ?string $mean_motion,
        public ?string $equinox,
        public ?OrbitClass $orbit_class,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            orbit_id: self::optionalText($data, 'orbit_id'),
            orbit_determination_date: self::optionalText($data, 'orbit_determination_date'),
            first_observation_date: self::optionalText($data, 'first_observation_date'),
            last_observation_date: self::optionalText($data, 'last_observation_date'),
            data_arc_in_days: self::optionalInt($data, 'data_arc_in_days'),
            observations_used: self::optionalInt($data, 'observations_used'),
            orbit_uncertainty: self::optionalText($data, 'orbit_uncertainty'),
            minimum_orbit_intersection: self::optionalText($data, 'minimum_orbit_intersection'),
            jupiter_tisserand_invariant: self::optionalText($data, 'jupiter_tisserand_invariant'),
            epoch_osculation: self::optionalText($data, 'epoch_osculation'),
            eccentricity: self::optionalText($data, 'eccentricity'),
            semi_major_axis: self::optionalText($data, 'semi_major_axis'),
            inclination: self::optionalText($data, 'inclination'),
            ascending_node_longitude: self::optionalText($data, 'ascending_node_longitude'),
            orbital_period: self::optionalText($data, 'orbital_period'),
            perihelion_distance: self::optionalText($data, 'perihelion_distance'),
            perihelion_argument: self::optionalText($data, 'perihelion_argument'),
            aphelion_distance: self::optionalText($data, 'aphelion_distance'),
            perihelion_time: self::optionalText($data, 'perihelion_time'),
            mean_anomaly: self::optionalText($data, 'mean_anomaly'),
            mean_motion: self::optionalText($data, 'mean_motion'),
            equinox: self::optionalText($data, 'equinox'),
            orbit_class: isset($data['orbit_class']) ? OrbitClass::fromArray((array) $data['orbit_class']) : null,
        );
    }
}
