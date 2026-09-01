<?php

namespace ProjectSaturnStudios\Stargazer\InSight\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;

final readonly class SolValidity implements HydratesFromArray
{
    public function __construct(
        public string $sol,
        public ?SensorValidity $temperature,
        public ?SensorValidity $windSpeed,
        public ?SensorValidity $pressure,
        public ?SensorValidity $windDirection,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            sol: (string) ($data['sol'] ?? ''),
            temperature: isset($data['AT']) && is_array($data['AT']) ? SensorValidity::fromArray($data['AT']) : null,
            windSpeed: isset($data['HWS']) && is_array($data['HWS']) ? SensorValidity::fromArray($data['HWS']) : null,
            pressure: isset($data['PRE']) && is_array($data['PRE']) ? SensorValidity::fromArray($data['PRE']) : null,
            windDirection: isset($data['WD']) && is_array($data['WD']) ? SensorValidity::fromArray($data['WD']) : null,
        );
    }
}
