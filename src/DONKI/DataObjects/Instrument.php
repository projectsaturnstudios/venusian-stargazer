<?php

namespace ProjectSaturnStudios\Stargazer\DONKI\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;

final readonly class Instrument implements HydratesFromArray
{
    use HydratesNasaData;

    public function __construct(
        public string $displayName,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            displayName: self::text($data, 'displayName'),
        );
    }
}
