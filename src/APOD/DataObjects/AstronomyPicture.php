<?php

namespace ProjectSaturnStudios\Stargazer\APOD\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;

final readonly class AstronomyPicture implements HydratesFromArray
{
    use HydratesNasaData;

    public function __construct(
        public string $date,
        public ?string $title,
        public ?string $explanation,
        public ?string $url,
        public ?string $hdurl,
        public ?string $media_type,
        public ?string $service_version,
        public ?string $copyright,
        public ?string $thumbnail_url,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            date: self::text($data, 'date'),
            title: self::optionalText($data, 'title'),
            explanation: self::optionalText($data, 'explanation'),
            url: self::optionalText($data, 'url'),
            hdurl: self::optionalText($data, 'hdurl'),
            media_type: self::optionalText($data, 'media_type'),
            service_version: self::optionalText($data, 'service_version'),
            copyright: self::optionalText($data, 'copyright'),
            thumbnail_url: self::optionalText($data, 'thumbnail_url'),
        );
    }
}
