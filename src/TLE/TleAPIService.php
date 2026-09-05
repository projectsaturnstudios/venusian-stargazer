<?php

namespace ProjectSaturnStudios\Stargazer\TLE;

use ProjectSaturnStudios\Stargazer\Enums\NasaURL;
use ProjectSaturnStudios\Stargazer\NasaApiService;
use ProjectSaturnStudios\Stargazer\PendingNasaRequest;
use ProjectSaturnStudios\Stargazer\TLE\DataObjects\TleCollection;
use ProjectSaturnStudios\Stargazer\TLE\DataObjects\TleRecord;
use Voyager\Contracts\IOPools\Completion;
use Voyager\IOPools\DTO\HttpResult;

class TleAPIService extends NasaApiService
{
    public function collection(): PendingNasaRequest
    {
        return $this->pending(
            base: NasaURL::TLE,
            path: 'tle',
            call_name: 'stargazer.tle.collection',
            hydrator: TleCollection::class,
            envelope: fn (HttpResult $result): Completion => static::resolveHttpResult($result, TleCollection::class),
        );
    }

    public function search(string $query): PendingNasaRequest
    {
        return $this->pending(
            base: NasaURL::TLE,
            path: 'tle',
            call_name: 'stargazer.tle.search',
            hydrator: TleCollection::class,
            query: ['search' => $query],
            envelope: fn (HttpResult $result): Completion => static::resolveHttpResult($result, TleCollection::class),
        );
    }

    public function satellite(int|string $id): PendingNasaRequest
    {
        return $this->pending(
            base: NasaURL::TLE,
            path: 'tle/'.$id,
            call_name: 'stargazer.tle.satellite',
            hydrator: TleRecord::class,
            envelope: fn (HttpResult $result): Completion => static::resolveHttpResult($result, TleRecord::class),
        );
    }

    /**
     * Shape the transport result into TLE mail. Collection and search
     * answer a TleCollection; satellite answers a TleRecord. The
     * endpoint's DTO rides in as $dto.
     *
     * @param  class-string  $dto
     */
    protected static function resolveHttpResult(HttpResult $result, string $dto): Completion
    {
        if (! $result->ok || $result->status >= 400) {
            return new TleFailed(
                name: $result->name,
                result: $result,
                reason: $result->error ?? "TLE answered status {$result->status}.",
            );
        }

        $payload = json_decode($result->body, true);

        if (! is_array($payload)) {
            return new TleFailed(
                name: $result->name,
                result: $result,
                reason: 'TLE body was not JSON.',
            );
        }

        return new TleArrived($result->name, $dto::fromArray($payload));
    }
}

