<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Vector;

/** Style colours ('#fff', '#rrggbb', 'rgb(…)', 'rgba(…)', or an rgba expression's result) as [r, g, b, a]. */
final class GibsStyleColor
{
    /** @return array{float, float, float, float}|null 0..255 channels, alpha 0..1; null when $value is no colour. */
    public static function parse(mixed $value): ?array
    {
        if (is_array($value) && count($value) === 4 && array_is_list($value)) {
            return array_map('floatval', $value);
        }
        if (! is_string($value)) {
            return null;
        }
        $value = strtolower(trim($value));
        if (preg_match('/^#([0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})$/', $value, $m)) {
            $hex = strlen($m[1]) <= 4 ? implode('', array_map(fn (string $c): string => $c.$c, str_split($m[1]))) : $m[1];
            $parts = array_map('hexdec', str_split($hex, 2));

            return [(float) $parts[0], (float) $parts[1], (float) $parts[2], isset($parts[3]) ? $parts[3] / 255 : 1.0];
        }
        if (preg_match('/^rgba?\(\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*([\d.]+)\s*(?:,\s*([\d.]+)\s*)?\)$/', $value, $m)) {
            return [(float) $m[1], (float) $m[2], (float) $m[3], isset($m[4]) ? (float) $m[4] : 1.0];
        }

        return null;
    }
}
