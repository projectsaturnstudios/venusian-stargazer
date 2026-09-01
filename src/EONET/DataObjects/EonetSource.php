<?php

namespace ProjectSaturnStudios\Stargazer\EONET\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;

final readonly class EonetSource implements HydratesFromArray
{
    use HydratesNasaData;

    public function __construct(
        public string $id,
        public ?string $title,
        public ?string $source,
        public ?string $url,
        public ?string $link,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            id: self::text($data, 'id'),
            title: self::optionalText($data, 'title'),
            source: self::optionalText($data, 'source'),
            url: self::optionalText($data, 'url'),
            link: self::optionalText($data, 'link'),
        );
    }
}
