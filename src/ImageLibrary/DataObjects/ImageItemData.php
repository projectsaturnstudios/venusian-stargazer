<?php

namespace ProjectSaturnStudios\Stargazer\ImageLibrary\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\ImageLibrary\Enums\ImageMediaType;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;
use Voyager\NutsAndBolts\Collection;

final readonly class ImageItemData implements HydratesFromArray
{
    use HydratesNasaData;

    /**
     * @param  Collection<int, string>  $keywords
     * @param  Collection<int, string>  $album
     */
    public function __construct(
        public string $nasaId,
        public ?string $title,
        public ?string $description,
        public ?string $description508,
        public ?string $center,
        public ?string $dateCreated,
        public ?ImageMediaType $mediaType,
        public Collection $keywords,
        public Collection $album,
        public ?string $photographer,
        public ?string $secondaryCreator,
        public ?string $location,
    ) {}

    public static function fromArray(array $data): static
    {
        $rawType = self::optionalText($data, 'media_type');

        return new self(
            nasaId: self::text($data, 'nasa_id'),
            title: self::optionalText($data, 'title'),
            description: self::optionalText($data, 'description'),
            description508: self::optionalText($data, 'description_508'),
            center: self::optionalText($data, 'center'),
            dateCreated: self::optionalText($data, 'date_created'),
            mediaType: is_null($rawType) ? null : ImageMediaType::tryFrom($rawType),
            keywords: self::stringList($data['keywords'] ?? []),
            album: self::stringList($data['album'] ?? []),
            photographer: self::optionalText($data, 'photographer'),
            secondaryCreator: self::optionalText($data, 'secondary_creator'),
            location: self::optionalText($data, 'location'),
        );
    }
}
