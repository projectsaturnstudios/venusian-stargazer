<?php

namespace ProjectSaturnStudios\Stargazer\DONKI\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;

final readonly class KpIndex implements HydratesFromArray
{
    use HydratesNasaData;

    public function __construct(
        public ?string $observedTime,
        public ?float $kpIndex,
        public ?string $source,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            observedTime: self::optionalText($data, 'observedTime'),
            kpIndex: self::optionalFloat($data, 'kpIndex'),
            source: self::optionalText($data, 'source'),
        );
    }
}
