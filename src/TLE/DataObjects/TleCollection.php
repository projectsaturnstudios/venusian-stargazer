<?php

namespace ProjectSaturnStudios\Stargazer\TLE\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use Voyager\NutsAndBolts\Collection;

final readonly class TleCollection implements HydratesFromArray
{
    /**
     * @param  Collection<int, TleRecord>  $members
     */
    public function __construct(
        public int $totalItems,
        public Collection $members,
        public ?string $context,
        public ?string $id,
        public ?string $type,
        public ?TleParameters $parameters,
        public ?TleView $view,
    ) {}

    public static function fromArray(array $data): static
    {
        $members = Collection::make((array) ($data['member'] ?? []))
            ->map(fn (mixed $row) => TleRecord::fromArray((array) $row));

        return new self(
            totalItems: (int) ($data['totalItems'] ?? $members->count()),
            members: $members,
            context: isset($data['@context']) ? (string) $data['@context'] : null,
            id: isset($data['@id']) ? (string) $data['@id'] : null,
            type: isset($data['@type']) ? (string) $data['@type'] : null,
            parameters: isset($data['parameters']) && is_array($data['parameters'])
                ? TleParameters::fromArray($data['parameters'])
                : null,
            view: isset($data['view']) && is_array($data['view'])
                ? TleView::fromArray($data['view'])
                : null,
        );
    }
}
