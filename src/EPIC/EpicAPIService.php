<?php

namespace ProjectSaturnStudios\Stargazer\EPIC;

use ProjectSaturnStudios\Stargazer\EPIC\DataObjects\EpicAvailableDate;
use ProjectSaturnStudios\Stargazer\EPIC\DataObjects\EpicImage;
use ProjectSaturnStudios\Stargazer\EPIC\Enums\EpicCollection;
use ProjectSaturnStudios\Stargazer\Enums\NasaURL;
use ProjectSaturnStudios\Stargazer\NasaApiService;
use ProjectSaturnStudios\Stargazer\PendingNasaRequest;

class EpicAPIService extends NasaApiService
{
    public function natural(?string $date = null): PendingNasaRequest
    {
        return $this->imagery(EpicCollection::NATURAL, $date);
    }

    public function enhanced(?string $date = null): PendingNasaRequest
    {
        return $this->imagery(EpicCollection::ENHANCED, $date);
    }

    public function naturalAvailable(): PendingNasaRequest
    {
        return $this->available(EpicCollection::NATURAL);
    }

    public function enhancedAvailable(): PendingNasaRequest
    {
        return $this->available(EpicCollection::ENHANCED);
    }

    protected function imagery(EpicCollection $collection, ?string $date): PendingNasaRequest
    {
        $path = is_null($date)
            ? 'api/'.$collection->value
            : 'api/'.$collection->value.'/date/'.$date;

        return $this->pending(
            base: NasaURL::EPIC,
            path: $path,
            call_name: 'stargazer.epic.'.$collection->value,
            hydrator: EpicImage::class,
        );
    }

    protected function available(EpicCollection $collection): PendingNasaRequest
    {
        $name = $collection === EpicCollection::NATURAL ? 'naturalAvailable' : 'enhancedAvailable';

        return $this->pending(
            base: NasaURL::EPIC,
            path: 'api/'.$collection->value.'/available',
            call_name: 'stargazer.epic.'.$name,
            hydrator: EpicAvailableDate::class,
        );
    }
}
