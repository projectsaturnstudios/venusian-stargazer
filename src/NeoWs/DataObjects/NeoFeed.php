<?php

namespace ProjectSaturnStudios\Stargazer\NeoWs\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;
use Voyager\NutsAndBolts\Collection;

final readonly class NeoFeed implements HydratesFromArray
{
    use HydratesNasaData;

    /**
     * @param  Collection<string, Collection<int, NearEarthObject>>  $near_earth_objects
     */
    public function __construct(
        public ?NeoLinks $links,
        public ?int $element_count,
        public Collection $near_earth_objects,
    ) {}

    public static function fromArray(array $data): static
    {
        $byDate = Collection::make($data['near_earth_objects'] ?? [])->map(
            fn (mixed $neos) => self::collectionOf($neos, NearEarthObject::class),
        );

        return new self(
            links: isset($data['links']) ? NeoLinks::fromArray((array) $data['links']) : null,
            element_count: self::optionalInt($data, 'element_count'),
            near_earth_objects: $byDate,
        );
    }
}
