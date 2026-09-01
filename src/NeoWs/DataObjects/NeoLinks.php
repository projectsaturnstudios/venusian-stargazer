<?php

namespace ProjectSaturnStudios\Stargazer\NeoWs\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;

final readonly class NeoLinks implements HydratesFromArray
{
    use HydratesNasaData;

    public function __construct(
        public ?string $self,
        public ?string $next,
        public ?string $previous,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            self: self::optionalText($data, 'self'),
            next: self::optionalText($data, 'next'),
            previous: self::optionalText($data, 'previous'),
        );
    }
}
