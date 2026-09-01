<?php

namespace ProjectSaturnStudios\Stargazer\DONKI\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;
use Voyager\NutsAndBolts\Collection;

final readonly class MagnetopauseCrossing implements HydratesFromArray
{
    use HydratesNasaData;

    /**
     * @param  Collection<int, Instrument>  $instruments
     * @param  Collection<int, LinkedEvent>|null  $linkedEvents
     * @param  Collection<int, SentNotification>|null  $sentNotifications
     */
    public function __construct(
        public string $mpcID,
        public ?string $eventTime,
        public Collection $instruments,
        public ?string $submissionTime,
        public ?int $versionId,
        public ?string $link,
        public ?Collection $linkedEvents,
        public ?Collection $sentNotifications,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            mpcID: self::text($data, 'mpcID'),
            eventTime: self::optionalText($data, 'eventTime'),
            instruments: self::collectionOf($data['instruments'] ?? [], Instrument::class),
            submissionTime: self::optionalText($data, 'submissionTime'),
            versionId: self::optionalInt($data, 'versionId'),
            link: self::optionalText($data, 'link'),
            linkedEvents: self::optionalCollection($data['linkedEvents'] ?? null, LinkedEvent::class),
            sentNotifications: self::optionalCollection($data['sentNotifications'] ?? null, SentNotification::class),
        );
    }
}
