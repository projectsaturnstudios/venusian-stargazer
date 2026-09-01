<?php

namespace ProjectSaturnStudios\Stargazer\EONET\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;
use Voyager\NutsAndBolts\Collection;

final readonly class EonetEventsPage implements HydratesFromArray
{
    use HydratesNasaData;

    /**
     * @param  Collection<int, EonetEvent>  $events
     */
    public function __construct(
        public ?string $title,
        public ?string $description,
        public ?string $link,
        public Collection $events,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            title: self::optionalText($data, 'title'),
            description: self::optionalText($data, 'description'),
            link: self::optionalText($data, 'link'),
            events: self::collectionOf($data['events'] ?? [], EonetEvent::class),
        );
    }
}
