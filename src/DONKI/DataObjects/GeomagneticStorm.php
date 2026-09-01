<?php

namespace ProjectSaturnStudios\Stargazer\DONKI\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;
use Voyager\NutsAndBolts\Collection;

final readonly class GeomagneticStorm implements HydratesFromArray
{
    use HydratesNasaData;

    /**
     * @param  Collection<int, KpIndex>  $allKpIndex
     * @param  Collection<int, LinkedEvent>|null  $linkedEvents
     * @param  Collection<int, SentNotification>|null  $sentNotifications
     */
    public function __construct(
        public string $gstID,
        public ?string $startTime,
        public Collection $allKpIndex,
        public ?string $link,
        public ?Collection $linkedEvents,
        public ?string $submissionTime,
        public ?int $versionId,
        public ?Collection $sentNotifications,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            gstID: self::text($data, 'gstID'),
            startTime: self::optionalText($data, 'startTime'),
            allKpIndex: self::collectionOf($data['allKpIndex'] ?? [], KpIndex::class),
            link: self::optionalText($data, 'link'),
            linkedEvents: self::optionalCollection($data['linkedEvents'] ?? null, LinkedEvent::class),
            submissionTime: self::optionalText($data, 'submissionTime'),
            versionId: self::optionalInt($data, 'versionId'),
            sentNotifications: self::optionalCollection($data['sentNotifications'] ?? null, SentNotification::class),
        );
    }
}
