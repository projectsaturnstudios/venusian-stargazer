<?php

namespace ProjectSaturnStudios\Stargazer\APOD;

use ProjectSaturnStudios\Stargazer\APOD\DataObjects\AstronomyPicture;
use ProjectSaturnStudios\Stargazer\Enums\NasaURL;
use ProjectSaturnStudios\Stargazer\NasaApiService;
use ProjectSaturnStudios\Stargazer\PendingNasaRequest;
use Voyager\Contracts\IOPools\Completion;
use Voyager\IOPools\DTO\HttpResult;

class ApodAPIService extends NasaApiService
{
    public function date(?string $date = null, bool $thumbs = false): PendingNasaRequest
    {
        $date ??= now(date_default_timezone_get())->toDateString();

        return $this->pending(
            NasaURL::APOD,
            '',
            'stargazer.apod.date',
            AstronomyPicture::class,
            $this->query([
                'date' => $date,
                'thumbs' => $thumbs ?: null,
            ]),
            envelope: fn (HttpResult $result): Completion => static::resolveHttpResult($result),
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
            envelope: fn (HttpResult $result): Completion => static::resolveHttpResult($result),
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
            envelope: fn (HttpResult $result): Completion => static::resolveHttpResult($result),
        );
    }

    /**
     * Shape the transport result into APOD mail. A sad conversation or an
     * unreadable body becomes APODFailed — the envelope always answers with
     * mail, never null, never a throw inside the tick.
     */
    protected static function resolveHttpResult(HttpResult $result): Completion
    {
        if (! $result->ok || $result->status >= 400) {
            return new APODFailed(
                name: $result->name,
                result: $result,
                reason: $result->error ?? "APOD answered status {$result->status}.",
            );
        }

        $payload = json_decode($result->body, true);

        if (! is_array($payload)) {
            return new APODFailed(
                name: $result->name,
                result: $result,
                reason: 'APOD body was not JSON.',
            );
        }

        // date() answers one picture as an object; range() and count()
        // answer a list. One envelope serves all three.
        $rows = array_is_list($payload) ? $payload : [$payload];

        $results = [];
        foreach ($rows as $apod) {
            $results[] = AstronomyPicture::fromArray((array) $apod);
        }

        return new APODArrived($result->name, $results);
    }
}
