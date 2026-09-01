<?php

namespace ProjectSaturnStudios\Stargazer\InSight\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;

final readonly class WindCompassPoint implements HydratesFromArray
{
    public function __construct(
        public float $compassDegrees,
        public string $compassPoint,
        public float $compassRight,
        public float $compassUp,
        public int $count,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            compassDegrees: (float) $data['compass_degrees'],
            compassPoint: (string) $data['compass_point'],
            compassRight: (float) $data['compass_right'],
            compassUp: (float) $data['compass_up'],
            count: (int) $data['ct'],
        );
    }
}
