<?php

namespace ProjectSaturnStudios\Stargazer;

use Closure;
use ProjectSaturnStudios\Stargazer\Enums\NasaURL;

class NasaApiService
{
    public function __construct(
        protected NasaClient $client,
    ) {}

    /**
     * @param  Closure(mixed):mixed|class-string  $hydrator
     * @param  array<string, mixed>  $query
     */
    public function pending(
        NasaURL $base,
        string $path,
        string $call_name,
        Closure|string $hydrator,
        array $query = [],
    ): PendingNasaRequest {
        return $this->client->pending($base, $path, $call_name, $hydrator, $query);
    }

    /**
     * @param  array<string, mixed>  $pairs
     * @return array<string, mixed>
     */
    protected function query(array $pairs): array
    {
        $query = [];

        foreach ($pairs as $name => $value) {
            if (is_null($value)) {
                continue;
            }

            $query[$name] = $value instanceof \BackedEnum ? $value->value : $value;
        }

        return $query;
    }
}
