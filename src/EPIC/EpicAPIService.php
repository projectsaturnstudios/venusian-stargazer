<?php

namespace ProjectSaturnStudios\Stargazer\EPIC;

use ProjectSaturnStudios\Stargazer\EPIC\DataObjects\EpicAvailableDate;
use ProjectSaturnStudios\Stargazer\EPIC\DataObjects\EpicImage;
use ProjectSaturnStudios\Stargazer\EPIC\Enums\EpicCollection;
use ProjectSaturnStudios\Stargazer\Enums\NasaURL;
use ProjectSaturnStudios\Stargazer\NasaApiService;
use ProjectSaturnStudios\Stargazer\PendingNasaRequest;
use Voyager\Contracts\IOPools\Completion;
use Voyager\IOPools\DTO\HttpResult;

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
            envelope: fn (HttpResult $result): Completion => static::resolveHttpResult($result, EpicImage::class),
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
            envelope: fn (HttpResult $result): Completion => static::resolveHttpResult($result, EpicAvailableDate::class),
        );
    }

    /**
     * Shape the transport result into EPIC mail. Every endpoint answers a
     * list; the endpoint's row DTO rides in as $dto.
     *
     * @param  class-string  $dto
     */
    protected static function resolveHttpResult(HttpResult $result, string $dto): Completion
    {
        if (! $result->ok || $result->status >= 400) {
            return new EpicFailed(
                name: $result->name,
                result: $result,
                reason: $result->error ?? "EPIC answered status {$result->status}.",
            );
        }

        $payload = json_decode($result->body, true);

        if (! is_array($payload)) {
            return new EpicFailed(
                name: $result->name,
                result: $result,
                reason: 'EPIC body was not JSON.',
            );
        }

        $items = [];
        foreach ($payload as $row) {
            $items[] = $dto::fromArray((array) $row);
        }

        return new EpicArrived($result->name, $items);
    }
}
