<?php

namespace ProjectSaturnStudios\Stargazer\TLE\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;

final readonly class TleParameters implements HydratesFromArray
{
    public function __construct(
        public ?string $search,
        public ?string $sort,
        public ?string $sortDirection,
        public ?int $page,
        public ?int $pageSize,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            search: isset($data['search']) ? (string) $data['search'] : null,
            sort: isset($data['sort']) ? (string) $data['sort'] : null,
            sortDirection: isset($data['sort-dir']) ? (string) $data['sort-dir'] : null,
            page: isset($data['page']) ? (int) $data['page'] : null,
            pageSize: isset($data['page-size']) ? (int) $data['page-size'] : null,
        );
    }
}
