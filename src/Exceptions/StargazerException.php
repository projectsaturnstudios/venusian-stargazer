<?php

namespace ProjectSaturnStudios\Stargazer\Exceptions;

use RuntimeException;

class StargazerException extends RuntimeException
{
    public static function loopNotBound(): self
    {
        return new self(
            'No event loop is bound to the Http client. async() needs the Voyager loop; call get() for a blocking request.',
        );
    }

    public static function httpClientUnavailable(): self
    {
        return new self(
            'The Voyager Http client is not available. Bind Voyager\\Http\\Client\\Factory as \'http\' or pass one to NasaClient.',
        );
    }

    public static function requestFailed(int $status, string $url, string $body): self
    {
        return new self("NASA request failed ({$status}) for {$url}: {$body}");
    }

    public static function invalidHydrator(mixed $hydrator): self
    {
        $label = is_string($hydrator) ? $hydrator : get_debug_type($hydrator);

        return new self("PendingNasaRequest hydrator [{$label}] must be a DTO class with fromArray() or a Closure.");
    }

    public static function invalidPayload(string $url): self
    {
        return new self("NASA response for {$url} was not a JSON object or list.");
    }
}
