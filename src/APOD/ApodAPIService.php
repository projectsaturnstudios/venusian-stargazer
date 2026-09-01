<?php

namespace ProjectSaturnStudios\Stargazer\APOD;

use ProjectSaturnStudios\Stargazer\APOD\DataObjects\AstronomyPicture;
use ProjectSaturnStudios\Stargazer\Enums\NasaURL;
use ProjectSaturnStudios\Stargazer\NasaApiService;
use ProjectSaturnStudios\Stargazer\PendingNasaRequest;

class ApodAPIService extends NasaApiService
{
    public function date(?string $date = null, bool $thumbs = false): PendingNasaRequest
    {
        return $this->pending(
            NasaURL::APOD,
            '',
            'stargazer.apod.date',
            AstronomyPicture::class,
            $this->query([
                'date' => $date,
                'thumbs' => $thumbs ?: null,
            ]),
        );
    }

    public function range(string $start_date, ?string $end_date = null, bool $thumbs = false): PendingNasaRequest
    {
        return $this->pending(
            NasaURL::APOD,
            '',
            'stargazer.apod.range',
            AstronomyPicture::class,
            $this->query([
                'start_date' => $start_date,
                'end_date' => $end_date,
                'thumbs' => $thumbs ?: null,
            ]),
        );
    }

    public function count(int $count, bool $thumbs = false): PendingNasaRequest
    {
        return $this->pending(
            NasaURL::APOD,
            '',
            'stargazer.apod.count',
            AstronomyPicture::class,
            $this->query([
                'count' => $count,
                'thumbs' => $thumbs ?: null,
            ]),
        );
    }
}
