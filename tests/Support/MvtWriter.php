<?php

namespace Tests\Support;

/**
 * Writes Mapbox Vector Tiles from plain arrays, so a test controls every
 * byte: layers, features, properties of each value type, and geometry.
 */
final class MvtWriter
{
    /**
     * @param  array<string, list<array{type: int, parts: list<list<array{int, int}>>, properties?: array<string, mixed>, id?: int}>>  $layers
     */
    public static function write(array $layers, int $extent = 4096): string
    {
        $tile = '';
        foreach ($layers as $name => $features) {
            $keys = [];
            $values = [];
            $body = self::field(15, 0, 2).self::field(1, 2, $name);
            foreach ($features as $feature) {
                $tags = [];
                foreach ($feature['properties'] ?? [] as $key => $value) {
                    $tags[] = $keys[$key] ??= count($keys);
                    $encoded = self::value($value);
                    $tags[] = $values[$encoded] ??= count($values);
                }
                $message = (isset($feature['id']) ? self::field(1, 0, $feature['id']) : '')
                    .self::field(2, 2, self::packed($tags))
                    .self::field(3, 0, $feature['type'])
                    .self::field(4, 2, self::packed(self::commands($feature['type'], $feature['parts'])));
                $body .= self::field(2, 2, $message);
            }
            foreach (array_keys($keys) as $key) {
                $body .= self::field(3, 2, (string) $key);
            }
            foreach (array_keys($values) as $value) {
                $body .= self::field(4, 2, $value);
            }
            $tile .= self::field(3, 2, $body.self::field(5, 0, $extent));
        }

        return $tile;
    }

    /** @return list<int> */
    private static function commands(int $type, array $parts): array
    {
        $commands = [];
        [$x, $y] = [0, 0];
        if ($type === 1) {
            $commands[] = 1 | (count($parts) << 3);
            foreach ($parts as [[$px, $py]]) {
                array_push($commands, self::zigzag($px - $x), self::zigzag($py - $y));
                [$x, $y] = [$px, $py];
            }

            return $commands;
        }
        foreach ($parts as $part) {
            [$px, $py] = $part[0];
            array_push($commands, 1 | (1 << 3), self::zigzag($px - $x), self::zigzag($py - $y));
            [$x, $y] = [$px, $py];
            $rest = array_slice($part, 1);
            $commands[] = 2 | (count($rest) << 3);
            foreach ($rest as [$px, $py]) {
                array_push($commands, self::zigzag($px - $x), self::zigzag($py - $y));
                [$x, $y] = [$px, $py];
            }
            if ($type === 3) {
                $commands[] = 7 | (1 << 3);
            }
        }

        return $commands;
    }

    private static function value(mixed $value): string
    {
        return match (true) {
            is_string($value) => self::field(1, 2, $value),
            is_bool($value) => self::field(7, 0, (int) $value),
            is_float($value) => self::field(3, 1, pack('e', $value)),
            $value < 0 => self::field(6, 0, self::zigzag($value)),
            default => self::field(5, 0, $value),
        };
    }

    private static function field(int $number, int $wire, int|string $value): string
    {
        $key = self::varint(($number << 3) | $wire);

        return match ($wire) {
            0 => $key.self::varint($value),
            1, 5 => $key.$value,
            2 => $key.self::varint(strlen($value)).$value,
        };
    }

    private static function packed(array $values): string
    {
        return implode('', array_map(self::varint(...), $values));
    }

    private static function varint(int $value): string
    {
        $bytes = '';
        do {
            $byte = $value & 0x7F;
            $value = ($value >> 7) & (PHP_INT_MAX >> 6);
            $bytes .= chr($value !== 0 ? $byte | 0x80 : $byte);
        } while ($value !== 0);

        return $bytes;
    }

    private static function zigzag(int $value): int
    {
        return ($value << 1) ^ ($value >> 63);
    }
}
