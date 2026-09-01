<?php

namespace ProjectSaturnStudios\Stargazer\DONKI\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;
use Voyager\NutsAndBolts\Collection;

final readonly class InterplanetaryShock implements HydratesFromArray
{
    use HydratesNasaData;

    /**
     * @param  Collection<int, Instrument>  $instruments
     * @param  Collection<int, LinkedEvent>|null  $linkedEvents
     * @param  Collection<int, SentNotification>|null  $sentNotifications
     */
    public function __construct(
        public string $activityID,
        public ?string $catalog,
        public ?string $location,
        public ?string $eventTime,
        public ?string $submissionTime,
        public ?int $versionId,
        public ?string $link,
        public Collection $instruments,
        public ?Collection $linkedEvents,
        public ?Collection $sentNotifications,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            activityID: self::text($data, 'activityID'),
            catalog: self::optionalText($data, 'catalog'),
            location: self::optionalText($data, 'location'),
            eventTime: self::optionalText($data, 'eventTime'),
            submissionTime: self::optionalText($data, 'submissionTime'),
            versionId: self::optionalInt($data, 'versionId'),
            link: self::optionalText($data, 'link'),
            instruments: self::collectionOf($data['instruments'] ?? [], Instrument::class),
            linkedEvents: self::optionalCollection($data['linkedEvents'] ?? null, LinkedEvent::class),
            sentNotifications: self::optionalCollection($data['sentNotifications'] ?? null, SentNotification::class),
        );
    }
}
