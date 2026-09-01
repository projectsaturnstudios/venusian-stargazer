<?php

namespace ProjectSaturnStudios\Stargazer\NeoWs\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;

final readonly class OrbitClass implements HydratesFromArray
{
    use HydratesNasaData;

    public function __construct(
        public ?string $orbit_class_type,
        public ?string $orbit_class_description,
        public ?string $orbit_class_range,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            orbit_class_type: self::optionalText($data, 'orbit_class_type'),
            orbit_class_description: self::optionalText($data, 'orbit_class_description'),
            orbit_class_range: self::optionalText($data, 'orbit_class_range'),
        );
    }
}
