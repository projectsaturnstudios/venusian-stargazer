<?php

namespace ProjectSaturnStudios\Stargazer\ImageLibrary\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;

/**
 * An item's metadata file: the JSON `metadata()` points at, ExifTool's reading of the original
 * file. A photographer's picture carries its camera and exposure; a processed image often
 * only its size. What the file leaves out, or leaves empty, is null.
 */
final readonly class ImageMetadata implements HydratesFromArray
{
    use HydratesNasaData;

    /**
     * @param  int|null  $width  the original's width in pixels
     * @param  int|null  $height  the original's height in pixels
     * @param  string|null  $make  the camera's maker, "NIKON CORPORATION"
     * @param  string|null  $model  the camera, "NIKON D4"
     * @param  string|null  $lens  "70.0-200.0 mm f/2.8"
     * @param  string|null  $exposureTime  "1/200"
     * @param  float|null  $fNumber  4.0
     * @param  int|null  $iso  1600
     * @param  string|null  $focalLength  "90.0 mm"
     * @param  string|null  $credit  who the file credits, "NASA/JPL"
     * @param  string|null  $caption  the caption written into the file, which may differ from the library's description
     */
    public function __construct(
        public ?int $width,
        public ?int $height,
        public ?string $make,
        public ?string $model,
        public ?string $lens,
        public ?string $exposureTime,
        public ?float $fNumber,
        public ?int $iso,
        public ?string $focalLength,
        public ?string $credit,
        public ?string $caption,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            width: self::firstInt($data, ['File:ImageWidth', 'EXIF:ExifImageWidth', 'EXIF:ImageWidth']),
            height: self::firstInt($data, ['File:ImageHeight', 'EXIF:ExifImageHeight', 'EXIF:ImageHeight']),
            make: self::firstText($data, ['EXIF:Make']),
            model: self::firstText($data, ['EXIF:Model']),
            lens: self::firstText($data, ['EXIF:LensModel', 'XMP:Lens', 'Composite:LensID']),
            exposureTime: self::firstText($data, ['EXIF:ExposureTime']),
            fNumber: is_null($f = self::firstText($data, ['EXIF:FNumber'])) ? null : (float) $f,
            iso: self::firstInt($data, ['EXIF:ISO']),
            focalLength: self::firstText($data, ['EXIF:FocalLength']),
            credit: self::firstText($data, ['XMP:Credit', 'IPTC:Credit', 'AVAIL:SecondaryCreator']),
            caption: self::firstText($data, ['XMP:Description', 'EXIF:ImageDescription', 'IPTC:Caption-Abstract']),
        );
    }

    /**
     * "NIKON D4": the model alone when it names the maker, as most do.
     */
    public function camera(): ?string
    {
        if (is_null($this->model)) {
            return $this->make;
        }
        if (is_null($this->make)) {
            return $this->model;
        }
        $maker = strtok($this->make, ' ');

        return stripos($this->model, (string) $maker) === 0 ? $this->model : "{$this->make} {$this->model}";
    }

    /**
     * The first of $keys the file fills, as trimmed text.
     *
     * @param  list<string>  $keys
     */
    private static function firstText(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $data[$key] ?? null;
            if (is_array($value)) {
                $value = implode(', ', array_filter($value, is_scalar(...)));
            }
            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $keys
     */
    private static function firstInt(array $data, array $keys): ?int
    {
        $text = self::firstText($data, $keys);

        return is_null($text) || ! is_numeric($text) ? null : (int) $text;
    }
}
