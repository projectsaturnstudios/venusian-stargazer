<?php

namespace ProjectSaturnStudios\Stargazer\NeoWs\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;

final readonly class MissDistance implements HydratesFromArray
{
    use HydratesNasaData;

    public function __construct(
        public ?string $astronomical,
        public ?string $lunar,
        public ?string $kilometers,
        public ?string $miles,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            astronomical: self::optionalText($data, 'astronomical'),
            lunar: self::optionalText($data, 'lunar'),
            kilometers: self::optionalText($data, 'kilometers'),
            miles: self::optionalText($data, 'miles'),
        );
    }
}
