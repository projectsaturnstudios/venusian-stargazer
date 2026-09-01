<?php

namespace ProjectSaturnStudios\Stargazer\NeoWs;

use ProjectSaturnStudios\Stargazer\Enums\NasaURL;
use ProjectSaturnStudios\Stargazer\NasaApiService;
use ProjectSaturnStudios\Stargazer\NeoWs\DataObjects\NearEarthObject;
use ProjectSaturnStudios\Stargazer\NeoWs\DataObjects\NeoBrowse;
use ProjectSaturnStudios\Stargazer\NeoWs\DataObjects\NeoFeed;
use ProjectSaturnStudios\Stargazer\PendingNasaRequest;

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
        );
    }

    public function lookup(string $asteroid_id): PendingNasaRequest
    {
        return $this->pending(
            NasaURL::NEOWS,
            'neo/'.$asteroid_id,
            'stargazer.neows.lookup',
            NearEarthObject::class,
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
        );
    }
}
