<?php

namespace ProjectSaturnStudios\Stargazer\ImageLibrary\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\ImageLibrary\ImageSidecarFailed;
use ProjectSaturnStudios\Stargazer\ImageLibrary\ImageSidecarReady;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;
use Voyager\Contracts\IOPools\Completion;
use Voyager\IOPools\DTO\HttpResult;
use Voyager\IOPools\Presumption;

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
     * Follow this location pointer through the io pool. The result
     * arrives as mail — ImageSidecarReady with the bytes,
     * ImageSidecarFailed when the conversation goes sour — so a
     * sketch listens instead of plumbing HTTP. Answers the
     * Presumption for hooks, or the in-flight one when this sidecar
     * is already downloading. The DTO has no nasa_id; the call name
     * keys off crc32 of the location URL so sync hydration stays put.
     */
    public function fetchAsync(): Presumption
    {
        $name = 'stargazer.imagelibrary.sidecar.'.crc32($this->location);
        $http = app('io-pool')->http();

        if (! is_null($in_flight = $http->inFlight($name))) {
            return $in_flight;
        }

        return $http->fetch(
            $name,
            $this->location,
            envelope: fn (HttpResult $result): Completion => ($result->ok && $result->status < 400)
                ? new ImageSidecarReady($this, $result)
                : new ImageSidecarFailed($this, $result, $result->error ?? "Sidecar answered status {$result->status}."),
        );
    }
}
