<?php

namespace ProjectSaturnStudios\Stargazer\TLE\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;

final readonly class TleView implements HydratesFromArray
{
    public function __construct(
        public ?string $id,
        public ?string $type,
        public ?string $first,
        public ?string $next,
        public ?string $previous,
        public ?string $last,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            id: isset($data['@id']) ? (string) $data['@id'] : null,
            type: isset($data['@type']) ? (string) $data['@type'] : null,
            first: isset($data['first']) ? (string) $data['first'] : null,
            next: isset($data['next']) ? (string) $data['next'] : null,
            previous: isset($data['previous']) ? (string) $data['previous'] : null,
            last: isset($data['last']) ? (string) $data['last'] : null,
        );
    }
}
