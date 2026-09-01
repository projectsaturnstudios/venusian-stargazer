<?php

namespace ProjectSaturnStudios\Stargazer\NeoWs\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;

final readonly class RelativeVelocity implements HydratesFromArray
{
    use HydratesNasaData;

    public function __construct(
        public ?string $kilometers_per_second,
        public ?string $kilometers_per_hour,
        public ?string $miles_per_hour,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            kilometers_per_second: self::optionalText($data, 'kilometers_per_second'),
            kilometers_per_hour: self::optionalText($data, 'kilometers_per_hour'),
            miles_per_hour: self::optionalText($data, 'miles_per_hour'),
        );
    }
}
