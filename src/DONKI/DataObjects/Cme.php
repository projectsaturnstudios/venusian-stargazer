<?php

namespace ProjectSaturnStudios\Stargazer\DONKI\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;
use Voyager\NutsAndBolts\Collection;

final readonly class Cme implements HydratesFromArray
{
    use HydratesNasaData;

    /**
     * @param  Collection<int, Instrument>  $instruments
     * @param  Collection<int, CmeAnalysis>|null  $cmeAnalyses
     * @param  Collection<int, LinkedEvent>|null  $linkedEvents
     * @param  Collection<int, SentNotification>|null  $sentNotifications
     */
    public function __construct(
        public string $activityID,
        public ?string $catalog,
        public ?string $startTime,
        public Collection $instruments,
        public ?string $sourceLocation,
        public ?int $activeRegionNum,
        public ?string $note,
        public ?string $submissionTime,
        public ?int $versionId,
        public ?string $link,
        public ?Collection $cmeAnalyses,
        public ?Collection $linkedEvents,
        public ?Collection $sentNotifications,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            activityID: self::text($data, 'activityID'),
            catalog: self::optionalText($data, 'catalog'),
            startTime: self::optionalText($data, 'startTime'),
            instruments: self::collectionOf($data['instruments'] ?? [], Instrument::class),
            sourceLocation: self::optionalText($data, 'sourceLocation'),
            activeRegionNum: self::optionalInt($data, 'activeRegionNum'),
            note: self::optionalText($data, 'note'),
            submissionTime: self::optionalText($data, 'submissionTime'),
            versionId: self::optionalInt($data, 'versionId'),
            link: self::optionalText($data, 'link'),
            cmeAnalyses: self::optionalCollection($data['cmeAnalyses'] ?? null, CmeAnalysis::class),
            linkedEvents: self::optionalCollection($data['linkedEvents'] ?? null, LinkedEvent::class),
            sentNotifications: self::optionalCollection($data['sentNotifications'] ?? null, SentNotification::class),
        );
    }
}
