<?php

namespace ProjectSaturnStudios\Stargazer\EPIC\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\EPIC\Enums\EpicCollection;
use ProjectSaturnStudios\Stargazer\EPIC\Enums\EpicImageType;
use ProjectSaturnStudios\Stargazer\Enums\NasaURL;
use Voyager\Contracts\IOPools\Promise;

final readonly class EpicImage implements HydratesFromArray
{
    public function __construct(
        public string $identifier,
        public string $caption,
        public string $image,
        public string $version,
        public string $date,
        public EpicCoordinates $centroid,
        public EpicVector $dscovrPosition,
        public EpicVector $lunarPosition,
        public EpicVector $sunPosition,
        public EpicQuaternions $attitude,
        public EpicCoordinateFrame $coords,
    ) {}

    public static function fromArray(array $data): static
    {
        $centroid = EpicCoordinates::fromArray((array) $data['centroid_coordinates']);
        $dscovr = EpicVector::fromArray((array) $data['dscovr_j2000_position']);
        $lunar = EpicVector::fromArray((array) $data['lunar_j2000_position']);
        $sun = EpicVector::fromArray((array) $data['sun_j2000_position']);
        $attitude = EpicQuaternions::fromArray((array) $data['attitude_quaternions']);

        return new self(
            identifier: (string) $data['identifier'],
            caption: (string) $data['caption'],
            image: (string) $data['image'],
            version: (string) $data['version'],
            date: (string) $data['date'],
            centroid: $centroid,
            dscovrPosition: $dscovr,
            lunarPosition: $lunar,
            sunPosition: $sun,
            attitude: $attitude,
            coords: EpicCoordinateFrame::fromArray((array) ($data['coords'] ?? [
                'centroid_coordinates' => $data['centroid_coordinates'],
                'dscovr_j2000_position' => $data['dscovr_j2000_position'],
                'lunar_j2000_position' => $data['lunar_j2000_position'],
                'sun_j2000_position' => $data['sun_j2000_position'],
                'attitude_quaternions' => $data['attitude_quaternions'],
            ])),
        );
    }

    public function archiveUrl(
        EpicCollection $collection,
        EpicImageType $type = EpicImageType::PNG,
    ): string {
        $stamp = substr($this->date, 0, 10);
        $parts = explode('-', $stamp);
        $year = $parts[0] ?? '';
        $month = $parts[1] ?? '';
        $day = $parts[2] ?? '';
        $extension = $type === EpicImageType::PNG ? 'png' : 'jpg';

        return rtrim(NasaURL::EPIC_ARCHIVE->value, '/').'/'
            .$collection->value.'/'.$year.'/'.$month.'/'.$day.'/'
            .$type->value.'/'.$this->image.'.'.$extension;
    }

    /**
     * Fetch this image's archive bytes on the event loop. The promise
     * fulfils with the Http Response; body() is the image.
     */
    public function render(
        EpicCollection $collection,
        EpicImageType $type = EpicImageType::PNG,
    ): Promise {
        return app('http')->async()->get($this->archiveUrl($collection, $type));
    }
}
