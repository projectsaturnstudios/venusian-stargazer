<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Metadata;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;

/** One property a vector layer's features carry, as its metadata describes it. */
final readonly class GibsMvtProperty implements HydratesFromArray
{
    use HydratesNasaData;

    /**
     * @param  list<array{Min: int|float, Max: int|float}>  $valueRanges
     * @param  array<int|string, mixed>  $valueMap
     * @param  list<mixed>  $valueList
     */
    public function __construct(
        public string $identifier,
        public string $title,
        public ?string $description,
        public ?string $units,
        public ?string $dataType,
        public ?string $function,
        public bool $isOptional,
        public bool $isLabel,
        public array $valueRanges,
        public array $valueMap,
        public array $valueList,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            identifier: self::text($data, 'Identifier'),
            title: self::text($data, 'Title'),
            description: self::optionalText($data, 'Description'),
            units: self::optionalText($data, 'Units'),
            dataType: self::optionalText($data, 'DataType'),
            function: self::optionalText($data, 'Function'),
            isOptional: (bool) ($data['IsOptional'] ?? false),
            isLabel: (bool) ($data['IsLabel'] ?? false),
            valueRanges: (array) ($data['ValueRanges'] ?? []),
            valueMap: (array) ($data['ValueMap'] ?? []),
            valueList: (array) ($data['ValueList'] ?? []),
        );
    }
}
