<?php

namespace ProjectSaturnStudios\Stargazer\DONKI\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;
use Voyager\NutsAndBolts\Collection;

final readonly class CmeAnalysis implements HydratesFromArray
{
    use HydratesNasaData;

    /**
     * @param  Collection<int, mixed>|null  $enlilList
     */
    public function __construct(
        public ?bool $isMostAccurate,
        public ?string $time21_5,
        public ?float $latitude,
        public ?float $longitude,
        public ?float $halfAngle,
        public ?float $speed,
        public ?string $type,
        public ?string $featureCode,
        public ?string $imageType,
        public ?string $measurementTechnique,
        public ?string $note,
        public ?int $levelOfData,
        public ?string $dataLevel,
        public ?float $tilt,
        public ?float $minorHalfWidth,
        public ?float $speedMeasuredAtHeight,
        public ?string $submissionTime,
        public ?int $versionId,
        public ?string $link,
        public ?string $associatedCMEID,
        public ?string $associatedCMEstartTime,
        public ?string $associatedCMELink,
        public ?string $catalog,
        public ?Collection $enlilList,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            isMostAccurate: self::optionalBool($data, 'isMostAccurate'),
            time21_5: self::optionalText($data, 'time21_5'),
            latitude: self::optionalFloat($data, 'latitude'),
            longitude: self::optionalFloat($data, 'longitude'),
            halfAngle: self::optionalFloat($data, 'halfAngle'),
            speed: self::optionalFloat($data, 'speed'),
            type: self::optionalText($data, 'type'),
            featureCode: self::optionalText($data, 'featureCode'),
            imageType: self::optionalText($data, 'imageType'),
            measurementTechnique: self::optionalText($data, 'measurementTechnique'),
            note: self::optionalText($data, 'note'),
            levelOfData: self::optionalInt($data, 'levelOfData'),
            dataLevel: self::optionalText($data, 'dataLevel'),
            tilt: self::optionalFloat($data, 'tilt'),
            minorHalfWidth: self::optionalFloat($data, 'minorHalfWidth'),
            speedMeasuredAtHeight: self::optionalFloat($data, 'speedMeasuredAtHeight'),
            submissionTime: self::optionalText($data, 'submissionTime'),
            versionId: self::optionalInt($data, 'versionId'),
            link: self::optionalText($data, 'link'),
            associatedCMEID: self::optionalText($data, 'associatedCMEID'),
            associatedCMEstartTime: self::optionalText($data, 'associatedCMEstartTime'),
            associatedCMELink: self::optionalText($data, 'associatedCMELink'),
            catalog: self::optionalText($data, 'catalog'),
            enlilList: self::optionalCollection($data['enlilList'] ?? null, WsaEnlilSimulation::class),
        );
    }
}
