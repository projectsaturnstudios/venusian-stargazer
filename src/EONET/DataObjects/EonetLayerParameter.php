<?php

namespace ProjectSaturnStudios\Stargazer\EONET\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;

final readonly class EonetLayerParameter implements HydratesFromArray
{
    use HydratesNasaData;

    public function __construct(
        public ?string $TILEMATRIXSET,
        public ?string $FORMAT,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            TILEMATRIXSET: self::optionalText($data, 'TILEMATRIXSET'),
            FORMAT: self::optionalText($data, 'FORMAT'),
        );
    }
}
