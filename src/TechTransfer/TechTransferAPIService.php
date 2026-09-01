<?php

namespace ProjectSaturnStudios\Stargazer\TechTransfer;

use ProjectSaturnStudios\Stargazer\Enums\NasaURL;
use ProjectSaturnStudios\Stargazer\NasaApiService;
use ProjectSaturnStudios\Stargazer\PendingNasaRequest;
use ProjectSaturnStudios\Stargazer\TechTransfer\DataObjects\TechTransferPage;
use ProjectSaturnStudios\Stargazer\TechTransfer\Enums\TechTransferCatalog;

class TechTransferAPIService extends NasaApiService
{
    public function patent(string $query): PendingNasaRequest
    {
        return $this->catalog(TechTransferCatalog::PATENT, $query, 'patent');
    }

    public function software(string $query): PendingNasaRequest
    {
        return $this->catalog(TechTransferCatalog::SOFTWARE, $query, 'software');
    }

    public function spinoff(string $query): PendingNasaRequest
    {
        return $this->catalog(TechTransferCatalog::SPINOFF, $query, 'Spinoff');
    }

    protected function catalog(TechTransferCatalog $catalog, string $query, string $parameter): PendingNasaRequest
    {
        return $this->pending(
            base: NasaURL::TECHTRANSFER,
            path: $catalog->value,
            call_name: 'stargazer.techtransfer.'.$catalog->value,
            hydrator: TechTransferPage::class,
            query: [$parameter => $query],
        );
    }
}
