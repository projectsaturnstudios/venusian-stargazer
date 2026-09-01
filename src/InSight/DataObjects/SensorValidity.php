<?php

namespace ProjectSaturnStudios\Stargazer\InSight\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;

final readonly class SensorValidity implements HydratesFromArray
{
    /**
     * @param  list<int>  $hoursWithData
     */
    public function __construct(
        public array $hoursWithData,
        public bool $valid,
    ) {}

    public static function fromArray(array $data): static
    {
        $hours = array_map(intval(...), (array) ($data['sol_hours_with_data'] ?? []));

        return new self(
            hoursWithData: $hours,
            valid: (bool) ($data['valid'] ?? false),
        );
    }
}
