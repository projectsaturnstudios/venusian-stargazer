<?php

namespace ProjectSaturnStudios\Stargazer\EONET\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;
use Voyager\NutsAndBolts\Collection;

final readonly class EonetCategory implements HydratesFromArray
{
    use HydratesNasaData;

    /**
     * @param  Collection<int, EonetLayer>|null  $layers
     */
    public function __construct(
        public string $id,
        public ?string $title,
        public ?string $description,
        public ?string $link,
        public string|Collection|null $layers,
    ) {}

    public static function fromArray(array $data): static
    {
        $layers = $data['layers'] ?? null;
        $hydratedLayers = is_array($layers)
            ? self::collectionOf($layers, EonetLayer::class)
            : self::optionalText($data, 'layers');

        return new self(
            id: self::text($data, 'id'),
            title: self::optionalText($data, 'title'),
            description: self::optionalText($data, 'description'),
            link: self::optionalText($data, 'link'),
            layers: $hydratedLayers,
        );
    }
}
