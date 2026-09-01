<?php

namespace ProjectSaturnStudios\Stargazer\DONKI\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;
use Voyager\NutsAndBolts\Collection;

final readonly class CmeInput implements HydratesFromArray
{
    use HydratesNasaData;

    /**
     * @param  Collection<int, LinkedEvent>  $ipsList
     */
    public function __construct(
        public ?string $cmeStartTime,
        public ?float $latitude,
        public ?float $longitude,
        public ?float $speed,
        public ?float $halfAngle,
        public ?string $time21_5,
        public ?string $featureCode,
        public ?bool $isMostAccurate,
        public ?int $levelOfData,
        public Collection $ipsList,
        public ?string $cmeid,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            cmeStartTime: self::optionalText($data, 'cmeStartTime'),
            latitude: self::optionalFloat($data, 'latitude'),
            longitude: self::optionalFloat($data, 'longitude'),
            speed: self::optionalFloat($data, 'speed'),
            halfAngle: self::optionalFloat($data, 'halfAngle'),
            time21_5: self::optionalText($data, 'time21_5'),
            featureCode: self::optionalText($data, 'featureCode'),
            isMostAccurate: self::optionalBool($data, 'isMostAccurate'),
            levelOfData: self::optionalInt($data, 'levelOfData'),
            ipsList: self::collectionOf($data['ipsList'] ?? [], LinkedEvent::class),
            cmeid: self::optionalText($data, 'cmeid'),
        );
    }
}
