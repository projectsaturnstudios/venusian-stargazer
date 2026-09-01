<?php

namespace ProjectSaturnStudios\Stargazer\ImageLibrary\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;
use Voyager\NutsAndBolts\Collection;

final readonly class ImageSearchPage implements HydratesFromArray
{
    use HydratesNasaData;

    /**
     * @param  Collection<int, ImageSearchItem>  $items
     * @param  Collection<int, ImageLink>  $links
     */
    public function __construct(
        public string $version,
        public string $href,
        public Collection $items,
        public Collection $links,
        public ?int $totalHits,
    ) {}

    public static function fromArray(array $data): static
    {
        $collection = is_array($data['collection'] ?? null) ? $data['collection'] : $data;
        $metadata = is_array($collection['metadata'] ?? null) ? $collection['metadata'] : [];

        return new self(
            version: self::text($collection, 'version'),
            href: self::text($collection, 'href'),
            items: self::collectionOf($collection['items'] ?? [], ImageSearchItem::class),
            links: self::collectionOf($collection['links'] ?? [], ImageLink::class),
            totalHits: self::optionalInt($metadata, 'total_hits'),
        );
    }
}
