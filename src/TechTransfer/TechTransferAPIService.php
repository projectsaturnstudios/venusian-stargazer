<?php

namespace ProjectSaturnStudios\Stargazer\TechTransfer;

use ProjectSaturnStudios\Stargazer\Enums\NasaURL;
use ProjectSaturnStudios\Stargazer\NasaApiService;
use ProjectSaturnStudios\Stargazer\PendingNasaRequest;
use ProjectSaturnStudios\Stargazer\TechTransfer\DataObjects\TechTransferPage;
use ProjectSaturnStudios\Stargazer\TechTransfer\Enums\TechTransferCatalog;
use Voyager\Contracts\IOPools\Completion;
use Voyager\IOPools\DTO\HttpResult;

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
            envelope: fn (HttpResult $result): Completion => static::resolveHttpResult($result, TechTransferPage::class),
        );
    }

    /**
     * Shape the transport result into TechTransfer mail. Every catalog
     * answers one TechTransferPage; the page DTO rides in as $dto.
     *
     * @param  class-string  $dto
     */
    protected static function resolveHttpResult(HttpResult $result, string $dto): Completion
    {
        if (! $result->ok || $result->status >= 400) {
            return new TechTransferFailed(
                name: $result->name,
                result: $result,
                reason: $result->error ?? "TechTransfer answered status {$result->status}.",
            );
        }

        $payload = json_decode($result->body, true);

        if (! is_array($payload)) {
            return new TechTransferFailed(
                name: $result->name,
                result: $result,
                reason: 'TechTransfer body was not JSON.',
            );
        }

        return new TechTransferArrived($result->name, $dto::fromArray($payload));
    }
}
