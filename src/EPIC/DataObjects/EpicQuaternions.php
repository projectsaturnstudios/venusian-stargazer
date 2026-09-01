<?php

namespace ProjectSaturnStudios\Stargazer\EPIC\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;

final readonly class EpicQuaternions implements HydratesFromArray
{
    public function __construct(
        public float $q0,
        public float $q1,
        public float $q2,
        public float $q3,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            q0: (float) $data['q0'],
            q1: (float) $data['q1'],
            q2: (float) $data['q2'],
            q3: (float) $data['q3'],
        );
    }
}
