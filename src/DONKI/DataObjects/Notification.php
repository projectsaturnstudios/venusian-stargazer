<?php

namespace ProjectSaturnStudios\Stargazer\DONKI\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;

final readonly class Notification implements HydratesFromArray
{
    use HydratesNasaData;

    public function __construct(
        public string $messageID,
        public ?string $messageType,
        public ?string $messageURL,
        public ?string $messageIssueTime,
        public ?string $messageBody,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            messageID: self::text($data, 'messageID'),
            messageType: self::optionalText($data, 'messageType'),
            messageURL: self::optionalText($data, 'messageURL'),
            messageIssueTime: self::optionalText($data, 'messageIssueTime'),
            messageBody: self::optionalText($data, 'messageBody'),
        );
    }
}
