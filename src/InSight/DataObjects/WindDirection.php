<?php

namespace ProjectSaturnStudios\Stargazer\InSight\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use Voyager\NutsAndBolts\Collection;

final readonly class WindDirection implements HydratesFromArray
{
    /**
     * @param  Collection<int, WindCompassPoint>  $points
     */
    public function __construct(
        public ?WindCompassPoint $mostCommon,
        public Collection $points,
    ) {}

    public static function fromArray(array $data): static
    {
        $most = $data['most_common'] ?? null;

        $points = Collection::make($data)
            ->reject(fn (mixed $value, mixed $key) => $key === 'most_common' || ! is_array($value))
            ->map(fn (array $row) => WindCompassPoint::fromArray($row))
            ->values();

        return new self(
            mostCommon: is_array($most) ? WindCompassPoint::fromArray($most) : null,
            points: $points,
        );
    }
}
