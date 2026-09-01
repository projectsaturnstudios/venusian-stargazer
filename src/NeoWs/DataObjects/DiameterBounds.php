<?php

namespace ProjectSaturnStudios\Stargazer\NeoWs\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;

final readonly class DiameterBounds implements HydratesFromArray
{
    use HydratesNasaData;

    public function __construct(
        public ?float $estimated_diameter_min,
        public ?float $estimated_diameter_max,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            estimated_diameter_min: self::optionalFloat($data, 'estimated_diameter_min'),
            estimated_diameter_max: self::optionalFloat($data, 'estimated_diameter_max'),
        );
    }
}
