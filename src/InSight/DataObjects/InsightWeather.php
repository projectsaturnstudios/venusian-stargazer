<?php

namespace ProjectSaturnStudios\Stargazer\InSight\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use Voyager\NutsAndBolts\Collection;

final readonly class InsightWeather implements HydratesFromArray
{
    /**
     * @param  list<string>  $solKeys
     * @param  Collection<int, InsightSol>  $sols
     */
    public function __construct(
        public array $solKeys,
        public Collection $sols,
        public ?InsightValidity $validity,
    ) {}

    public static function fromArray(array $data): static
    {
        $keys = array_map(strval(...), (array) ($data['sol_keys'] ?? []));

        $sols = Collection::make($keys)->map(function (string $sol) use ($data) {
            $row = $data[$sol] ?? [];

            return InsightSol::fromArray(['sol' => $sol] + (array) $row);
        });

        $validity = isset($data['validity_checks']) && is_array($data['validity_checks'])
            ? InsightValidity::fromArray($data['validity_checks'])
            : null;

        return new self(
            solKeys: $keys,
            sols: $sols,
            validity: $validity,
        );
    }

    public function sol(string $sol): ?InsightSol
    {
        $match = $this->sols->first(fn (InsightSol $row) => $row->sol === $sol);

        return $match instanceof InsightSol ? $match : null;
    }
}
