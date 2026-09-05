<?php

namespace ProjectSaturnStudios\Stargazer\NeoWs;

use ProjectSaturnStudios\Stargazer\Enums\NasaURL;
use ProjectSaturnStudios\Stargazer\NasaApiService;
use ProjectSaturnStudios\Stargazer\NeoWs\DataObjects\NearEarthObject;
use ProjectSaturnStudios\Stargazer\NeoWs\DataObjects\NeoBrowse;
use ProjectSaturnStudios\Stargazer\NeoWs\DataObjects\NeoFeed;
use ProjectSaturnStudios\Stargazer\PendingNasaRequest;
use Voyager\Contracts\IOPools\Completion;
use Voyager\IOPools\DTO\HttpResult;

class NeowsAPIService extends NasaApiService
{
    public function feed(?string $start_date = null, ?string $end_date = null): PendingNasaRequest
    {
        return $this->pending(
            NasaURL::NEOWS,
            'feed',
            'stargazer.neows.feed',
            NeoFeed::class,
            $this->query([
                'start_date' => $start_date,
                'end_date' => $end_date,
            ]),
            envelope: fn (HttpResult $result): Completion => static::resolveHttpResult($result, NeoFeed::class),
        );
    }

    public function lookup(string $asteroid_id): PendingNasaRequest
    {
        return $this->pending(
            NasaURL::NEOWS,
            'neo/'.$asteroid_id,
            'stargazer.neows.lookup',
            NearEarthObject::class,
            envelope: fn (HttpResult $result): Completion => static::resolveHttpResult($result, NearEarthObject::class),
        );
    }

    public function browse(?int $page = null, ?int $size = null): PendingNasaRequest
    {
        return $this->pending(
            NasaURL::NEOWS,
            'neo/browse',
            'stargazer.neows.browse',
            NeoBrowse::class,
            $this->query([
                'page' => $page,
                'size' => $size,
            ]),
            envelope: fn (HttpResult $result): Completion => static::resolveHttpResult($result, NeoBrowse::class),
        );
    }

    /**
     * Shape the transport result into NeoWs mail. Feed answers a NeoFeed,
     * lookup a NearEarthObject, browse a NeoBrowse. The endpoint's DTO
     * rides in as $dto.
     *
     * @param  class-string  $dto
     */
    protected static function resolveHttpResult(HttpResult $result, string $dto): Completion
    {
        if (! $result->ok || $result->status >= 400) {
            return new NeowsFailed(
                name: $result->name,
                result: $result,
                reason: $result->error ?? "NeoWs answered status {$result->status}.",
            );
        }

        $payload = json_decode($result->body, true);

        if (! is_array($payload)) {
            return new NeowsFailed(
                name: $result->name,
                result: $result,
                reason: 'NeoWs body was not JSON.',
            );
        }

        return new NeowsArrived($result->name, $dto::fromArray($payload));
    }
}
