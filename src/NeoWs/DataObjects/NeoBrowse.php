<?php

namespace ProjectSaturnStudios\Stargazer\NeoWs\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;
use Voyager\NutsAndBolts\Collection;

final readonly class NeoBrowse implements HydratesFromArray
{
    use HydratesNasaData;

    /**
     * @param  Collection<int, NearEarthObject>  $near_earth_objects
     */
    public function __construct(
        public ?NeoLinks $links,
        public ?NeoPage $page,
        public Collection $near_earth_objects,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            links: isset($data['links']) ? NeoLinks::fromArray((array) $data['links']) : null,
            page: isset($data['page']) ? NeoPage::fromArray((array) $data['page']) : null,
            near_earth_objects: self::collectionOf($data['near_earth_objects'] ?? [], NearEarthObject::class),
        );
    }
}
