<?php

namespace ProjectSaturnStudios\Stargazer\ImageLibrary;

use ProjectSaturnStudios\Stargazer\Enums\NasaURL;
use ProjectSaturnStudios\Stargazer\ImageLibrary\DataObjects\ImageAssetManifest;
use ProjectSaturnStudios\Stargazer\ImageLibrary\DataObjects\ImageLocation;
use ProjectSaturnStudios\Stargazer\ImageLibrary\DataObjects\ImageSearchPage;
use ProjectSaturnStudios\Stargazer\NasaApiService;
use ProjectSaturnStudios\Stargazer\PendingNasaRequest;
use Voyager\Contracts\IOPools\Completion;
use Voyager\IOPools\DTO\HttpResult;

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
            envelope: fn (HttpResult $result): Completion => static::resolveHttpResult($result, ImageSearchPage::class),
        );
    }

    public function asset(string $nasa_id): PendingNasaRequest
    {
        return $this->pending(
            base: NasaURL::IMAGE_LIBRARY,
            path: 'asset/'.$nasa_id,
            call_name: 'stargazer.imagelibrary.asset',
            hydrator: ImageAssetManifest::class,
            envelope: fn (HttpResult $result): Completion => static::resolveHttpResult($result, ImageAssetManifest::class),
        );
    }

    public function metadata(string $nasa_id): PendingNasaRequest
    {
        return $this->pending(
            base: NasaURL::IMAGE_LIBRARY,
            path: 'metadata/'.$nasa_id,
            call_name: 'stargazer.imagelibrary.metadata',
            hydrator: ImageLocation::class,
            envelope: fn (HttpResult $result): Completion => static::resolveHttpResult($result, ImageLocation::class),
        );
    }

    public function captions(string $nasa_id): PendingNasaRequest
    {
        return $this->pending(
            base: NasaURL::IMAGE_LIBRARY,
            path: 'captions/'.$nasa_id,
            call_name: 'stargazer.imagelibrary.captions',
            hydrator: ImageLocation::class,
            envelope: fn (HttpResult $result): Completion => static::resolveHttpResult($result, ImageLocation::class),
        );
    }

    /**
     * Shape the transport result into Image Library mail. Search answers
     * an ImageSearchPage, asset an ImageAssetManifest, metadata and
     * captions an ImageLocation. The endpoint's DTO rides in as $dto.
     *
     * @param  class-string  $dto
     */
    protected static function resolveHttpResult(HttpResult $result, string $dto): Completion
    {
        if (! $result->ok || $result->status >= 400) {
            return new ImageLibraryFailed(
                name: $result->name,
                result: $result,
                reason: $result->error ?? "Image Library answered status {$result->status}.",
            );
        }

        $payload = json_decode($result->body, true);

        if (! is_array($payload)) {
            return new ImageLibraryFailed(
                name: $result->name,
                result: $result,
                reason: 'Image Library body was not JSON.',
            );
        }

        return new ImageLibraryArrived($result->name, $dto::fromArray($payload));
    }
}
