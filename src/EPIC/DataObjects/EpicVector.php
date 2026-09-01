<?php

namespace ProjectSaturnStudios\Stargazer\EPIC\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;

final readonly class EpicVector implements HydratesFromArray
{
    public function __construct(
        public float $x,
        public float $y,
        public float $z,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            x: (float) $data['x'],
            y: (float) $data['y'],
            z: (float) $data['z'],
        );
    }
}
