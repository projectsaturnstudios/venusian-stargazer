<?php

namespace ProjectSaturnStudios\Stargazer\DONKI\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;

final readonly class LinkedEvent implements HydratesFromArray
{
    use HydratesNasaData;

    public function __construct(
        public string $activityID,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            activityID: self::text($data, 'activityID'),
        );
    }
}
