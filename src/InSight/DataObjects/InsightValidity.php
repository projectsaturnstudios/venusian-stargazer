<?php

namespace ProjectSaturnStudios\Stargazer\InSight\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use Voyager\NutsAndBolts\Collection;

final readonly class InsightValidity implements HydratesFromArray
{
    /**
     * @param  list<string>  $solsChecked
     * @param  Collection<string, SolValidity>  $sols
     */
    public function __construct(
        public int $hoursRequired,
        public array $solsChecked,
        public Collection $sols,
    ) {}

    public static function fromArray(array $data): static
    {
        $checked = array_map(strval(...), (array) ($data['sols_checked'] ?? []));

        $sols = Collection::make($data)
            ->reject(fn (mixed $value, mixed $key) => in_array($key, ['sol_hours_required', 'sols_checked'], true) || ! is_array($value))
            ->map(fn (array $row, mixed $sol) => SolValidity::fromArray(['sol' => (string) $sol] + $row));

        return new self(
            hoursRequired: (int) ($data['sol_hours_required'] ?? 0),
            solsChecked: $checked,
            sols: $sols,
        );
    }

    public function forSol(string $sol): ?SolValidity
    {
        $match = $this->sols->first(fn (SolValidity $row) => $row->sol === $sol);

        return $match instanceof SolValidity ? $match : null;
    }
}
