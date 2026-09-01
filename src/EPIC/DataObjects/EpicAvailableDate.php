<?php

namespace ProjectSaturnStudios\Stargazer\EPIC\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;

final readonly class EpicAvailableDate implements HydratesFromArray
{
    public function __construct(
        public string $date,
    ) {}

    public static function fromArray(array $data): static
    {
        $date = $data['date'] ?? $data[0] ?? '';

        return new self(date: (string) $date);
    }
}
