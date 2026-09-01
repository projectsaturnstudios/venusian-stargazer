<?php

namespace ProjectSaturnStudios\Stargazer\EONET\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;

final readonly class EonetMagnitude implements HydratesFromArray
{
    use HydratesNasaData;

    public function __construct(
        public string $id,
        public ?string $name,
        public ?string $unit,
        public ?string $description,
        public ?string $link,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            id: self::text($data, 'id'),
            name: self::optionalText($data, 'name'),
            unit: self::optionalText($data, 'unit'),
            description: self::optionalText($data, 'description'),
            link: self::optionalText($data, 'link'),
        );
    }
}
