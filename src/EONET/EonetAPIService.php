<?php

namespace ProjectSaturnStudios\Stargazer\EONET;

use ProjectSaturnStudios\Stargazer\Enums\NasaURL;
use ProjectSaturnStudios\Stargazer\EONET\DataObjects\EonetCategoriesPage;
use ProjectSaturnStudios\Stargazer\EONET\DataObjects\EonetEventsPage;
use ProjectSaturnStudios\Stargazer\EONET\DataObjects\EonetLayersPage;
use ProjectSaturnStudios\Stargazer\EONET\DataObjects\EonetMagnitudesPage;
use ProjectSaturnStudios\Stargazer\EONET\DataObjects\EonetSourcesPage;
use ProjectSaturnStudios\Stargazer\NasaApiService;
use ProjectSaturnStudios\Stargazer\PendingNasaRequest;

class EonetAPIService extends NasaApiService
{
    public function events(): PendingNasaRequest
    {
        return $this->eonet('events', 'events', EonetEventsPage::class);
    }

    public function categories(?string $id = null): PendingNasaRequest
    {
        $path = is_null($id) ? 'categories' : 'categories/'.$id;

        return $this->eonet($path, 'categories', EonetCategoriesPage::class);
    }

    public function sources(): PendingNasaRequest
    {
        return $this->eonet('sources', 'sources', EonetSourcesPage::class);
    }

    public function layers(?string $id = null): PendingNasaRequest
    {
        $path = is_null($id) ? 'layers' : 'layers/'.$id;

        return $this->eonet($path, 'layers', EonetLayersPage::class);
    }

    public function magnitudes(): PendingNasaRequest
    {
        return $this->eonet('magnitudes', 'magnitudes', EonetMagnitudesPage::class);
    }

    /**
     * @param  class-string  $dto
     */
    protected function eonet(string $path, string $endpoint, string $dto): PendingNasaRequest
    {
        return $this->pending(
            NasaURL::EONET,
            $path,
            'stargazer.eonet.'.$endpoint,
            $dto,
        );
    }
}
