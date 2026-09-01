<?php

namespace ProjectSaturnStudios\Stargazer\ImageLibrary\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;

final readonly class ImageLink implements HydratesFromArray
{
    use HydratesNasaData;

    public function __construct(
        public string $href,
        public ?string $rel,
        public ?string $prompt,
        public ?string $render,
        public ?int $width,
        public ?int $height,
        public ?int $size,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            href: self::text($data, 'href'),
            rel: self::optionalText($data, 'rel'),
            prompt: self::optionalText($data, 'prompt'),
            render: self::optionalText($data, 'render'),
            width: self::optionalInt($data, 'width'),
            height: self::optionalInt($data, 'height'),
            size: self::optionalInt($data, 'size'),
        );
    }
}
