<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Support;

/**
 * KVP query strings written the way GIBS documents them. GIBS reads a TIME
 * range only with its '/' as is ('Invalid periods start date' for %2F), so
 * '/', ',' and ':' stay literal; everything else is percent-encoded.
 */
final class GibsUrl
{
    /** @param array<string, string|int|float> $params */
    public static function kvp(string $path, array $params): string
    {
        $pairs = [];
        foreach ($params as $name => $value) {
            $pairs[] = rawurlencode($name).'='.str_replace(['%2F', '%2C', '%3A'], ['/', ',', ':'], rawurlencode((string) $value));
        }

        return $path.'?'.implode('&', $pairs);
    }
}
