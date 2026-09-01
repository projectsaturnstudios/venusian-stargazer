<?php

namespace ProjectSaturnStudios\Stargazer\EONET\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;
use Voyager\NutsAndBolts\Collection;

final readonly class EonetLayer implements HydratesFromArray
{
    use HydratesNasaData;

    /**
     * @param  Collection<int, EonetLayerParameter>  $parameters
     */
    public function __construct(
        public string $name,
        public ?string $serviceUrl,
        public ?string $serviceTypeId,
        public Collection $parameters,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            name: self::text($data, 'name'),
            serviceUrl: self::optionalText($data, 'serviceUrl'),
            serviceTypeId: self::optionalText($data, 'serviceTypeId'),
            parameters: self::collectionOf($data['parameters'] ?? [], EonetLayerParameter::class),
        );
    }
}
