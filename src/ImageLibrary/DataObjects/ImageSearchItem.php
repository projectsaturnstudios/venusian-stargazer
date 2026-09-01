<?php

namespace ProjectSaturnStudios\Stargazer\ImageLibrary\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;
use Voyager\NutsAndBolts\Collection;

final readonly class ImageSearchItem implements HydratesFromArray
{
    use HydratesNasaData;

    /**
     * @param  Collection<int, ImageItemData>  $data
     * @param  Collection<int, ImageLink>  $links
     */
    public function __construct(
        public string $href,
        public Collection $data,
        public Collection $links,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            href: self::text($data, 'href'),
            data: self::collectionOf($data['data'] ?? [], ImageItemData::class),
            links: self::collectionOf($data['links'] ?? [], ImageLink::class),
        );
    }
}
