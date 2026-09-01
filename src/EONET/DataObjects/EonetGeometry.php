<?php

namespace ProjectSaturnStudios\Stargazer\EONET\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;

final readonly class EonetGeometry implements HydratesFromArray
{
    use HydratesNasaData;

    /**
     * @param  array<int, mixed>|null  $coordinates
     */
    public function __construct(
        public ?float $magnitudeValue,
        public ?string $magnitudeUnit,
        public ?string $date,
        public ?string $type,
        public ?array $coordinates,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            magnitudeValue: self::optionalFloat($data, 'magnitudeValue'),
            magnitudeUnit: self::optionalText($data, 'magnitudeUnit'),
            date: self::optionalText($data, 'date'),
            type: self::optionalText($data, 'type'),
            coordinates: isset($data['coordinates']) && is_array($data['coordinates']) ? $data['coordinates'] : null,
        );
    }
}
