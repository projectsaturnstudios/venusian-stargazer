<?php

namespace ProjectSaturnStudios\Stargazer\NeoWs\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;

final readonly class NeoPage implements HydratesFromArray
{
    use HydratesNasaData;

    public function __construct(
        public ?int $size,
        public ?int $total_elements,
        public ?int $total_pages,
        public ?int $number,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            size: self::optionalInt($data, 'size'),
            total_elements: self::optionalInt($data, 'total_elements'),
            total_pages: self::optionalInt($data, 'total_pages'),
            number: self::optionalInt($data, 'number'),
        );
    }
}
