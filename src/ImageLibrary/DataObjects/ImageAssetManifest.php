<?php

namespace ProjectSaturnStudios\Stargazer\ImageLibrary\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;
use Voyager\NutsAndBolts\Collection;

final readonly class ImageAssetManifest implements HydratesFromArray
{
    use HydratesNasaData;

    /**
     * @param  Collection<int, ImageAssetFile>  $items
     */
    public function __construct(
        public string $version,
        public string $href,
        public Collection $items,
    ) {}

    public static function fromArray(array $data): static
    {
        $collection = is_array($data['collection'] ?? null) ? $data['collection'] : $data;

        return new self(
            version: self::text($collection, 'version'),
            href: self::text($collection, 'href'),
            items: self::collectionOf($collection['items'] ?? [], ImageAssetFile::class),
        );
    }
}
