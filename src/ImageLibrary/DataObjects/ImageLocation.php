<?php

namespace ProjectSaturnStudios\Stargazer\ImageLibrary\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;

final readonly class ImageLocation implements HydratesFromArray
{
    use HydratesNasaData;

    public function __construct(
        public string $location,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            location: self::text($data, 'location'),
        );
    }
}
