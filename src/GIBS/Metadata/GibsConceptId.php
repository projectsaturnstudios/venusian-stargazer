<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Metadata;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;

/** A CMR collection a layer is made from. */
final readonly class GibsConceptId implements HydratesFromArray
{
    use HydratesNasaData;

    public function __construct(
        public string $type,
        public string $value,
        public ?string $shortName,
        public ?string $title,
        public ?string $version,
        public ?string $dataCenter,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            type: self::text($data, 'type'),
            value: self::text($data, 'value'),
            shortName: self::optionalText($data, 'shortName'),
            title: self::optionalText($data, 'title'),
            version: self::optionalText($data, 'version'),
            dataCenter: self::optionalText($data, 'dataCenter'),
        );
    }
}
