<?php

namespace ProjectSaturnStudios\Stargazer\NeoWs\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;

final readonly class CloseApproach implements HydratesFromArray
{
    use HydratesNasaData;

    public function __construct(
        public ?string $close_approach_date,
        public ?string $close_approach_date_full,
        public ?int $epoch_date_close_approach,
        public ?RelativeVelocity $relative_velocity,
        public ?MissDistance $miss_distance,
        public ?string $orbiting_body,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            close_approach_date: self::optionalText($data, 'close_approach_date'),
            close_approach_date_full: self::optionalText($data, 'close_approach_date_full'),
            epoch_date_close_approach: self::optionalInt($data, 'epoch_date_close_approach'),
            relative_velocity: isset($data['relative_velocity']) ? RelativeVelocity::fromArray((array) $data['relative_velocity']) : null,
            miss_distance: isset($data['miss_distance']) ? MissDistance::fromArray((array) $data['miss_distance']) : null,
            orbiting_body: self::optionalText($data, 'orbiting_body'),
        );
    }
}
