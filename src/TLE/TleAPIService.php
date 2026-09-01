<?php

namespace ProjectSaturnStudios\Stargazer\TLE;

use ProjectSaturnStudios\Stargazer\Enums\NasaURL;
use ProjectSaturnStudios\Stargazer\NasaApiService;
use ProjectSaturnStudios\Stargazer\PendingNasaRequest;
use ProjectSaturnStudios\Stargazer\TLE\DataObjects\TleCollection;
use ProjectSaturnStudios\Stargazer\TLE\DataObjects\TleRecord;

class TleAPIService extends NasaApiService
{
    public function collection(): PendingNasaRequest
    {
        return $this->pending(
            base: NasaURL::TLE,
            path: 'tle',
            call_name: 'stargazer.tle.collection',
            hydrator: TleCollection::class,
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
        );
    }

    public function satellite(int|string $id): PendingNasaRequest
    {
        return $this->pending(
            base: NasaURL::TLE,
            path: 'tle/'.$id,
            call_name: 'stargazer.tle.satellite',
            hydrator: TleRecord::class,
        );
    }
}
