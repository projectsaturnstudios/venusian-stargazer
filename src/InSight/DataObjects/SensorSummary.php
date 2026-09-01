<?php

namespace ProjectSaturnStudios\Stargazer\InSight\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;

final readonly class SensorSummary implements HydratesFromArray
{
    public function __construct(
        public float $average,
        public int $count,
        public float $min,
        public float $max,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            average: (float) $data['av'],
            count: (int) $data['ct'],
            min: (float) $data['mn'],
            max: (float) $data['mx'],
        );
    }
}
