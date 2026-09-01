<?php

namespace ProjectSaturnStudios\Stargazer\DONKI\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;
use Voyager\NutsAndBolts\Collection;

final readonly class WsaEnlilSimulation implements HydratesFromArray
{
    use HydratesNasaData;

    /**
     * @param  Collection<int, CmeInput>  $cmeInputs
     * @param  Collection<int, Impact>|null  $impactList
     */
    public function __construct(
        public string $simulationID,
        public ?string $modelCompletionTime,
        public ?float $au,
        public Collection $cmeInputs,
        public ?string $estimatedShockArrivalTime,
        public ?string $estimatedDuration,
        public ?float $rmin_re,
        public ?float $kp_18,
        public ?float $kp_90,
        public ?float $kp_135,
        public ?float $kp_180,
        public ?bool $isEarthGB,
        public ?bool $isEarthMinorImpact,
        public ?Collection $impactList,
        public ?string $link,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            simulationID: self::text($data, 'simulationID'),
            modelCompletionTime: self::optionalText($data, 'modelCompletionTime'),
            au: self::optionalFloat($data, 'au'),
            cmeInputs: self::collectionOf($data['cmeInputs'] ?? [], CmeInput::class),
            estimatedShockArrivalTime: self::optionalText($data, 'estimatedShockArrivalTime'),
            estimatedDuration: self::optionalText($data, 'estimatedDuration'),
            rmin_re: self::optionalFloat($data, 'rmin_re'),
            kp_18: self::optionalFloat($data, 'kp_18'),
            kp_90: self::optionalFloat($data, 'kp_90'),
            kp_135: self::optionalFloat($data, 'kp_135'),
            kp_180: self::optionalFloat($data, 'kp_180'),
            isEarthGB: self::optionalBool($data, 'isEarthGB'),
            isEarthMinorImpact: self::optionalBool($data, 'isEarthMinorImpact'),
            impactList: self::optionalCollection($data['impactList'] ?? null, Impact::class),
            link: self::optionalText($data, 'link'),
        );
    }
}
