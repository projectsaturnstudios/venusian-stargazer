<?php

namespace ProjectSaturnStudios\Stargazer\Support;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use Voyager\NutsAndBolts\Collection;

trait HydratesNasaData
{
    protected static function text(array $data, string $key, string $default = ''): string
    {
        return array_key_exists($key, $data) && ! is_null($data[$key]) ? (string) $data[$key] : $default;
    }

    protected static function optionalText(array $data, string $key): ?string
    {
        if (! array_key_exists($key, $data) || is_null($data[$key])) {
            return null;
        }

        return (string) $data[$key];
    }

    protected static function optionalInt(array $data, string $key): ?int
    {
        if (! array_key_exists($key, $data) || is_null($data[$key]) || $data[$key] === '') {
            return null;
        }

        return (int) $data[$key];
    }

    protected static function optionalFloat(array $data, string $key): ?float
    {
        if (! array_key_exists($key, $data) || is_null($data[$key]) || $data[$key] === '') {
            return null;
        }

        return (float) $data[$key];
    }

    protected static function optionalBool(array $data, string $key): ?bool
    {
        if (! array_key_exists($key, $data) || is_null($data[$key])) {
            return null;
        }

        return (bool) $data[$key];
    }

    /**
     * @return Collection<int, string>
     */
    protected static function stringList(mixed $rows): Collection
    {
        if (! is_array($rows)) {
            return Collection::make();
        }

        $values = [];

        foreach ($rows as $row) {
            if (is_null($row) || $row === '') {
                continue;
            }

            $values[] = (string) $row;
        }

        return Collection::make($values);
    }

    /**
     * @param  class-string<HydratesFromArray>  $dto
     */
    protected static function collectionOf(mixed $rows, string $dto): Collection
    {
        if (! is_array($rows)) {
            return Collection::make();
        }

        return Collection::make($rows)->map(
            fn (mixed $row) => $dto::fromArray((array) $row),
        );
    }

    /**
     * @param  class-string<HydratesFromArray>  $dto
     */
    protected static function optionalCollection(mixed $rows, string $dto): ?Collection
    {
        if (is_null($rows)) {
            return null;
        }

        return self::collectionOf($rows, $dto);
    }
}
