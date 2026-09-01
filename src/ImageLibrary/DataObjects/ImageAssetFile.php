<?php

namespace ProjectSaturnStudios\Stargazer\ImageLibrary\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;

final readonly class ImageAssetFile implements HydratesFromArray
{
    use HydratesNasaData;

    public function __construct(
        public string $href,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            href: self::text($data, 'href'),
        );
    }
}
