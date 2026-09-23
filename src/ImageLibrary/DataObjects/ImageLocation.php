<?php

namespace ProjectSaturnStudios\Stargazer\ImageLibrary\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;
use Voyager\Contracts\IOPools\Promise;

final readonly class ImageLocation implements HydratesFromArray
{
    use HydratesNasaData;

    public function __construct(
        public string $location,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            location: self::text($data, 'location'),
        );
    }

    /**
     * Fetch this sidecar on the event loop. Library hrefs carry raw
     * spaces (nasa_ids like "Webb First Images"); curl refuses them, so
     * encode at the wire. The DTO keeps the raw location.
     */
    public function fetch(): Promise
    {
        return app('http')->async()->get(str_replace(' ', '%20', $this->location));
    }
}
