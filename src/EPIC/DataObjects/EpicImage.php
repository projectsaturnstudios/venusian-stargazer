<?php

namespace ProjectSaturnStudios\Stargazer\EPIC\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\EPIC\EpicImageFailed;
use ProjectSaturnStudios\Stargazer\EPIC\EpicImageReady;
use ProjectSaturnStudios\Stargazer\EPIC\Enums\EpicCollection;
use ProjectSaturnStudios\Stargazer\EPIC\Enums\EpicImageType;
use ProjectSaturnStudios\Stargazer\Enums\NasaURL;
use Voyager\Contracts\IOPools\Completion;
use Voyager\IOPools\DTO\HttpResult;
use Voyager\IOPools\Presumption;

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

        return rtrim(NasaURL::EPIC->value, '/').'/archive/'
            .$collection->value.'/'.$year.'/'.$month.'/'.$day.'/'
            .$type->value.'/'.$this->image.'.'.$extension;
    }

    /**
     * Follow this image's archive link through the io pool. The result
     * arrives as mail — EpicImageReady with the bytes, EpicImageFailed
     * when the conversation goes sour — so a sketch listens instead of
     * plumbing HTTP. Answers the Presumption for hooks (progress and all),
     * or the in-flight one when this image is already downloading.
     */
    public function renderAsync(
        EpicCollection $collection,
        EpicImageType $type = EpicImageType::PNG,
    ): Presumption {
        $extension = $type === EpicImageType::PNG ? 'png' : 'jpg';
        $name = "stargazer.epic.image.{$this->identifier}";
        $http = app('io-pool')->http();

        if (! is_null($in_flight = $http->inFlight($name))) {
            return $in_flight;
        }

        return $http->fetch(
            $name,
            $this->archiveUrl($collection, $type),
            envelope: fn (HttpResult $result): Completion => ($result->ok && $result->status < 400)
                ? new EpicImageReady($this, $result, $extension)
                : new EpicImageFailed($this, $result, $result->error ?? "Archive answered status {$result->status}."),
        );
    }
}
