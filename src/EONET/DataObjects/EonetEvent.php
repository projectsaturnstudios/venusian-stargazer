<?php

namespace ProjectSaturnStudios\Stargazer\EONET\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;
use Voyager\NutsAndBolts\Collection;

final readonly class EonetEvent implements HydratesFromArray
{
    use HydratesNasaData;

    /**
     * @param  Collection<int, EonetCategory>  $categories
     * @param  Collection<int, EonetSource>  $sources
     * @param  Collection<int, EonetGeometry>  $geometry
     */
    public function __construct(
        public string $id,
        public ?string $title,
        public ?string $description,
        public ?string $link,
        public ?string $closed,
        public Collection $categories,
        public Collection $sources,
        public Collection $geometry,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            id: self::text($data, 'id'),
            title: self::optionalText($data, 'title'),
            description: self::optionalText($data, 'description'),
            link: self::optionalText($data, 'link'),
            closed: self::optionalText($data, 'closed'),
            categories: self::collectionOf($data['categories'] ?? [], EonetCategory::class),
            sources: self::collectionOf($data['sources'] ?? [], EonetSource::class),
            geometry: self::collectionOf($data['geometry'] ?? [], EonetGeometry::class),
        );
    }
}
