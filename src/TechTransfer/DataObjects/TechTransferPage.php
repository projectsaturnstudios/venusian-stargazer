<?php

namespace ProjectSaturnStudios\Stargazer\TechTransfer\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use Voyager\NutsAndBolts\Collection;

final readonly class TechTransferPage implements HydratesFromArray
{
    /**
     * @param  Collection<int, TechTransferRecord>  $results
     */
    public function __construct(
        public Collection $results,
        public int $count,
        public int $total,
        public int $perPage,
        public int $page,
    ) {}

    public static function fromArray(array $data): static
    {
        $results = Collection::make((array) ($data['results'] ?? []))
            ->map(fn (mixed $row) => TechTransferRecord::fromArray((array) $row));

        return new self(
            results: $results,
            count: (int) ($data['count'] ?? $results->count()),
            total: (int) ($data['total'] ?? $results->count()),
            perPage: (int) ($data['perpage'] ?? $results->count()),
            page: (int) ($data['page'] ?? 0),
        );
    }
}
