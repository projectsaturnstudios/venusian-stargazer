<?php

namespace ProjectSaturnStudios\Stargazer\EPIC\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;

final readonly class EpicCoordinateFrame implements HydratesFromArray
{
    public function __construct(
        public EpicCoordinates $centroid,
        public EpicVector $dscovrPosition,
        public EpicVector $lunarPosition,
        public EpicVector $sunPosition,
        public EpicQuaternions $attitude,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            centroid: EpicCoordinates::fromArray((array) $data['centroid_coordinates']),
            dscovrPosition: EpicVector::fromArray((array) $data['dscovr_j2000_position']),
            lunarPosition: EpicVector::fromArray((array) $data['lunar_j2000_position']),
            sunPosition: EpicVector::fromArray((array) $data['sun_j2000_position']),
            attitude: EpicQuaternions::fromArray((array) $data['attitude_quaternions']),
        );
    }
}
