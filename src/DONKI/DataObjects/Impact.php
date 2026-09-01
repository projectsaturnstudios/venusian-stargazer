<?php

namespace ProjectSaturnStudios\Stargazer\DONKI\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;

final readonly class Impact implements HydratesFromArray
{
    use HydratesNasaData;

    public function __construct(
        public ?bool $isGlancingBlow,
        public ?string $location,
        public ?string $arrivalTime,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            isGlancingBlow: self::optionalBool($data, 'isGlancingBlow'),
            location: self::optionalText($data, 'location'),
            arrivalTime: self::optionalText($data, 'arrivalTime'),
        );
    }
}
