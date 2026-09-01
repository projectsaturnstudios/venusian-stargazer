<?php

namespace ProjectSaturnStudios\Stargazer\EPIC\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;

final readonly class EpicCoordinates implements HydratesFromArray
{
    public function __construct(
        public float $lat,
        public float $lon,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            lat: (float) $data['lat'],
            lon: (float) $data['lon'],
        );
    }
}
