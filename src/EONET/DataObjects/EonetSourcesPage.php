<?php

namespace ProjectSaturnStudios\Stargazer\EONET\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;
use Voyager\NutsAndBolts\Collection;

final readonly class EonetSourcesPage implements HydratesFromArray
{
    use HydratesNasaData;

    /**
     * @param  Collection<int, EonetSource>  $sources
     */
    public function __construct(
        public ?string $title,
        public ?string $description,
        public ?string $link,
        public Collection $sources,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            title: self::optionalText($data, 'title'),
            description: self::optionalText($data, 'description'),
            link: self::optionalText($data, 'link'),
            sources: self::collectionOf($data['sources'] ?? [], EonetSource::class),
        );
    }
}
