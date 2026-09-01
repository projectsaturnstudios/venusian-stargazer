<?php

namespace ProjectSaturnStudios\Stargazer\TLE\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;

final readonly class TleRecord implements HydratesFromArray
{
    public function __construct(
        public int $satelliteId,
        public string $name,
        public string $date,
        public string $line1,
        public string $line2,
        public ?string $id = null,
        public ?string $type = null,
        public ?string $context = null,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            satelliteId: (int) $data['satelliteId'],
            name: (string) $data['name'],
            date: (string) $data['date'],
            line1: (string) $data['line1'],
            line2: (string) $data['line2'],
            id: isset($data['@id']) ? (string) $data['@id'] : null,
            type: isset($data['@type']) ? (string) $data['@type'] : null,
            context: isset($data['@context']) ? (string) $data['@context'] : null,
        );
    }
}
