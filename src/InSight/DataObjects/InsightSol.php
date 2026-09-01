<?php

namespace ProjectSaturnStudios\Stargazer\InSight\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\InSight\Enums\InsightSeason;

final readonly class InsightSol implements HydratesFromArray
{
    public function __construct(
        public string $sol,
        public ?InsightSeason $season,
        public ?string $firstUtc,
        public ?string $lastUtc,
        public ?SensorSummary $temperature,
        public ?SensorSummary $windSpeed,
        public ?SensorSummary $pressure,
        public ?WindDirection $windDirection,
    ) {}

    public static function fromArray(array $data): static
    {
        $season = isset($data['Season']) ? InsightSeason::tryFrom(strtolower((string) $data['Season'])) : null;

        return new self(
            sol: (string) ($data['sol'] ?? ''),
            season: $season,
            firstUtc: isset($data['First_UTC']) ? (string) $data['First_UTC'] : null,
            lastUtc: isset($data['Last_UTC']) ? (string) $data['Last_UTC'] : null,
            temperature: isset($data['AT']) && is_array($data['AT']) ? SensorSummary::fromArray($data['AT']) : null,
            windSpeed: isset($data['HWS']) && is_array($data['HWS']) ? SensorSummary::fromArray($data['HWS']) : null,
            pressure: isset($data['PRE']) && is_array($data['PRE']) ? SensorSummary::fromArray($data['PRE']) : null,
            windDirection: isset($data['WD']) && is_array($data['WD']) ? WindDirection::fromArray($data['WD']) : null,
        );
    }
}
