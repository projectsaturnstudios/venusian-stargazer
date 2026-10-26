<?php

namespace ProjectSaturnStudios\Stargazer\Trek\DataObjects;

use ProjectSaturnStudios\Stargazer\GIBS\DataObjects\GibsBox;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;
use ProjectSaturnStudios\Stargazer\Trek\Enums\TrekBody;
use ProjectSaturnStudios\Stargazer\Trek\Enums\TrekProjection;
use Throwable;

/** One map layer in a Trek layer list. Its tiles and capabilities live under tiles/{Body}/{projection}/{label}. */
final readonly class TrekLayer
{
    use HydratesNasaData;

    public function __construct(
        public string $label,
        public string $title,
        public TrekBody $body,
        public TrekProjection $projection,
        public ?GibsBox $box,
        public ?string $mission,
        public ?string $instrument,
        public ?string $category,
        public ?string $kind,
        public ?string $coverage,
        public ?string $thumbnailUrl,
    ) {}

    /** @param array<string, mixed> $data One row of a layer list. */
    public static function fromRow(array $data, TrekBody $body, TrekProjection $projection): self
    {
        $thumbnail = self::optionalText($data, 'thumbnailURLDir');

        return new self(
            label: self::text($data, 'productLabel'),
            title: self::text($data, 'title'),
            body: $body,
            projection: $projection,
            box: self::box(self::optionalText($data, 'bbox')),
            mission: self::optionalText($data, 'mission'),
            instrument: self::optionalText($data, 'instrument'),
            category: self::optionalText($data, 'productCat1'),
            kind: self::optionalText($data, 'productCat2'),
            coverage: self::optionalText($data, 'coverage'),
            // Trek's own page adds the size: a folder prefix, or a whole file name already.
            thumbnailUrl: match (true) {
                is_null($thumbnail) || $thumbnail === '' => null,
                str_ends_with($thumbnail, '.png') || str_ends_with($thumbnail, '.jpg') => $thumbnail,
                default => $thumbnail.'120.png',
            },
        );
    }

    /** 'minX,minY,maxX,maxY' in degrees; null when absent or not four ordered numbers. */
    protected static function box(?string $bbox): ?GibsBox
    {
        $parts = is_null($bbox) ? [] : explode(',', $bbox);
        if (count($parts) !== 4 || array_filter($parts, fn (string $part): bool => ! is_numeric(trim($part))) !== []) {
            return null;
        }

        try {
            return new GibsBox(...array_map(fn (string $part): float => (float) $part, $parts));
        } catch (Throwable) {
            return null;
        }
    }
}
