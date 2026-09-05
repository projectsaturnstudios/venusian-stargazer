<?php

namespace ProjectSaturnStudios\Stargazer\InSight;

use ProjectSaturnStudios\Stargazer\Enums\NasaURL;
use ProjectSaturnStudios\Stargazer\InSight\DataObjects\InsightWeather;
use ProjectSaturnStudios\Stargazer\NasaApiService;
use ProjectSaturnStudios\Stargazer\PendingNasaRequest;
use Voyager\Contracts\IOPools\Completion;
use Voyager\IOPools\DTO\HttpResult;

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
            envelope: fn (HttpResult $result): Completion => static::resolveHttpResult($result),
        );
    }

    /**
     * Shape the transport result into InSight mail. The feed answers one
     * object, so the envelope hydrates one InsightWeather.
     */
    protected static function resolveHttpResult(HttpResult $result): Completion
    {
        if (! $result->ok || $result->status >= 400) {
            return new InsightFailed(
                name: $result->name,
                result: $result,
                reason: $result->error ?? "InSight answered status {$result->status}.",
            );
        }

        $payload = json_decode($result->body, true);

        if (! is_array($payload)) {
            return new InsightFailed(
                name: $result->name,
                result: $result,
                reason: 'InSight body was not JSON.',
            );
        }

        return new InsightArrived($result->name, InsightWeather::fromArray($payload));
    }
}
