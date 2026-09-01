<?php

namespace ProjectSaturnStudios\Stargazer\NeoWs\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;

final readonly class EstimatedDiameter implements HydratesFromArray
{
    public function __construct(
        public ?DiameterBounds $kilometers,
        public ?DiameterBounds $meters,
        public ?DiameterBounds $miles,
        public ?DiameterBounds $feet,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            kilometers: isset($data['kilometers']) ? DiameterBounds::fromArray((array) $data['kilometers']) : null,
            meters: isset($data['meters']) ? DiameterBounds::fromArray((array) $data['meters']) : null,
            miles: isset($data['miles']) ? DiameterBounds::fromArray((array) $data['miles']) : null,
            feet: isset($data['feet']) ? DiameterBounds::fromArray((array) $data['feet']) : null,
        );
    }
}
