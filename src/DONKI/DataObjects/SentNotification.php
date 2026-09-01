<?php

namespace ProjectSaturnStudios\Stargazer\DONKI\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;

final readonly class SentNotification implements HydratesFromArray
{
    use HydratesNasaData;

    public function __construct(
        public string $messageID,
        public ?string $messageIssueTime,
        public ?string $messageURL,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            messageID: self::text($data, 'messageID'),
            messageIssueTime: self::optionalText($data, 'messageIssueTime'),
            messageURL: self::optionalText($data, 'messageURL'),
        );
    }
}
