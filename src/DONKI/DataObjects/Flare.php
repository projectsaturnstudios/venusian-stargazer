<?php

namespace ProjectSaturnStudios\Stargazer\DONKI\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;
use Voyager\NutsAndBolts\Collection;

final readonly class Flare implements HydratesFromArray
{
    use HydratesNasaData;

    /**
     * @param  Collection<int, Instrument>  $instruments
     * @param  Collection<int, LinkedEvent>|null  $linkedEvents
     * @param  Collection<int, SentNotification>|null  $sentNotifications
     */
    public function __construct(
        public string $flrID,
        public ?string $catalog,
        public Collection $instruments,
        public ?string $beginTime,
        public ?string $peakTime,
        public ?string $endTime,
        public ?string $classType,
        public ?string $sourceLocation,
        public ?int $activeRegionNum,
        public ?string $note,
        public ?string $submissionTime,
        public ?int $versionId,
        public ?string $link,
        public ?Collection $linkedEvents,
        public ?Collection $sentNotifications,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            flrID: self::text($data, 'flrID'),
            catalog: self::optionalText($data, 'catalog'),
            instruments: self::collectionOf($data['instruments'] ?? [], Instrument::class),
            beginTime: self::optionalText($data, 'beginTime'),
            peakTime: self::optionalText($data, 'peakTime'),
            endTime: self::optionalText($data, 'endTime'),
            classType: self::optionalText($data, 'classType'),
            sourceLocation: self::optionalText($data, 'sourceLocation'),
            activeRegionNum: self::optionalInt($data, 'activeRegionNum'),
            note: self::optionalText($data, 'note'),
            submissionTime: self::optionalText($data, 'submissionTime'),
            versionId: self::optionalInt($data, 'versionId'),
            link: self::optionalText($data, 'link'),
            linkedEvents: self::optionalCollection($data['linkedEvents'] ?? null, LinkedEvent::class),
            sentNotifications: self::optionalCollection($data['sentNotifications'] ?? null, SentNotification::class),
        );
    }
}
