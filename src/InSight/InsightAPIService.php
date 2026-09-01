<?php

namespace ProjectSaturnStudios\Stargazer\InSight;

use ProjectSaturnStudios\Stargazer\Enums\NasaURL;
use ProjectSaturnStudios\Stargazer\InSight\DataObjects\InsightWeather;
use ProjectSaturnStudios\Stargazer\NasaApiService;
use ProjectSaturnStudios\Stargazer\PendingNasaRequest;

class InsightAPIService extends NasaApiService
{
    public function weather(): PendingNasaRequest
    {
        return $this->pending(
            base: NasaURL::INSIGHT,
            path: '',
            call_name: 'stargazer.insight.weather',
            hydrator: InsightWeather::class,
            query: [
                'feedtype' => 'json',
                'ver' => '1.0',
            ],
        );
    }
}
