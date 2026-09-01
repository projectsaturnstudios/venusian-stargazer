<?php

namespace ProjectSaturnStudios\Stargazer\ImageLibrary;

use ProjectSaturnStudios\Stargazer\Enums\NasaURL;
use ProjectSaturnStudios\Stargazer\ImageLibrary\DataObjects\ImageAssetManifest;
use ProjectSaturnStudios\Stargazer\ImageLibrary\DataObjects\ImageLocation;
use ProjectSaturnStudios\Stargazer\ImageLibrary\DataObjects\ImageSearchPage;
use ProjectSaturnStudios\Stargazer\NasaApiService;
use ProjectSaturnStudios\Stargazer\PendingNasaRequest;

class ImageLibraryAPIService extends NasaApiService
{
    public function search(?string $q = null): PendingNasaRequest
    {
        return $this->pending(
            base: NasaURL::IMAGE_LIBRARY,
            path: 'search',
            call_name: 'stargazer.imagelibrary.search',
            hydrator: ImageSearchPage::class,
            query: $this->query(['q' => $q]),
        );
    }

    public function asset(string $nasa_id): PendingNasaRequest
    {
        return $this->pending(
            base: NasaURL::IMAGE_LIBRARY,
            path: 'asset/'.$nasa_id,
            call_name: 'stargazer.imagelibrary.asset',
            hydrator: ImageAssetManifest::class,
        );
    }

    public function metadata(string $nasa_id): PendingNasaRequest
    {
        return $this->pending(
            base: NasaURL::IMAGE_LIBRARY,
            path: 'metadata/'.$nasa_id,
            call_name: 'stargazer.imagelibrary.metadata',
            hydrator: ImageLocation::class,
        );
    }

    public function captions(string $nasa_id): PendingNasaRequest
    {
        return $this->pending(
            base: NasaURL::IMAGE_LIBRARY,
            path: 'captions/'.$nasa_id,
            call_name: 'stargazer.imagelibrary.captions',
            hydrator: ImageLocation::class,
        );
    }
}
