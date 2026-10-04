<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Vector;

use Closure;
use ProjectSaturnStudios\Stargazer\Exceptions\StargazerException;

/**
 * Mapbox GL style expressions, evaluated for one feature at one zoom. Covers
 * every operator GIBS's vector styles use (case, match, step, interpolate,
 * get, zoom, geometry-type, comparisons, all, %, rgba, literal) and the
 * common rest: any, !, has, coalesce, arithmetic, min/max, rounding, rgb,
 * to-number, to-string, to-boolean, concat, upcase, downcase. Colours come
 * back as [r, g, b, a] (0..255, alpha 0..1).
 */
final class GibsStyleExpression
{
    public const array OPERATORS = [
        'literal', 'get', 'has', '!', '==', '!=', '<', '<=', '>', '>=', 'all', 'any', 'case', 'match', 'step', 'interpolate',
        'zoom', 'geometry-type', '+', '-', '*', '/', '%', '^', 'min', 'max', 'abs', 'round', 'floor', 'ceil', 'rgb', 'rgba',
        'to-number', 'to-string', 'to-boolean', 'number', 'string', 'boolean', 'coalesce', 'concat', 'upcase', 'downcase',
    ];

    /**
     * @param  array<string, mixed>  $properties
     * @throws StargazerException When the expression uses an operator outside OPERATORS.
     */
    public static function evaluate(mixed $expression, float $zoom, array $properties = [], GibsGeometryType $type = GibsGeometryType::UNKNOWN): mixed
    {
        if (! is_array($expression) || $expression === [] || ! is_string($expression[0])) {
            return $expression;
        }

        $op = $expression[0];
        $args = array_slice($expression, 1);
        $at = fn (int $i): mixed => self::evaluate($args[$i] ?? null, $zoom, $properties, $type);

        return match ($op) {
            'literal' => $args[0] ?? null,
            'get' => $properties[$at(0)] ?? null,
            'has' => array_key_exists($at(0), $properties),
            '!' => ! self::truthy($at(0)),
            '==' => self::equal($at(0), $at(1)),
            '!=' => ! self::equal($at(0), $at(1)),
            '<', '<=', '>', '>=' => self::compare($op, $at(0), $at(1)),
            'all' => array_reduce(array_keys($args), fn (bool $all, int $i): bool => $all && self::truthy($at($i)), true),
            'any' => array_reduce(array_keys($args), fn (bool $any, int $i): bool => $any || self::truthy($at($i)), false),
            'case' => self::case($args, $at),
            'match' => self::match($args, $at),
            'step' => self::step($args, $at),
            'interpolate' => self::interpolate($args, $at),
            'zoom' => $zoom,
            'geometry-type' => $type->styleName(),
            '+' => array_sum(array_map(fn (int $i): float => (float) $at($i), array_keys($args))),
            '*' => array_product(array_map(fn (int $i): float => (float) $at($i), array_keys($args))),
            '-' => count($args) === 1 ? -(float) $at(0) : (float) $at(0) - (float) $at(1),
            '/' => (float) $at(1) == 0.0 ? NAN : (float) $at(0) / (float) $at(1),
            '%' => (float) $at(1) == 0.0 ? NAN : fmod((float) $at(0), (float) $at(1)),
            '^' => (float) $at(0) ** (float) $at(1),
            'min' => min(array_map(fn (int $i): float => (float) $at($i), array_keys($args))),
            'max' => max(array_map(fn (int $i): float => (float) $at($i), array_keys($args))),
            'abs' => abs((float) $at(0)),
            'round' => round((float) $at(0)),
            'floor' => floor((float) $at(0)),
            'ceil' => ceil((float) $at(0)),
            'rgb' => [(float) $at(0), (float) $at(1), (float) $at(2), 1.0],
            'rgba' => [(float) $at(0), (float) $at(1), (float) $at(2), (float) $at(3)],
            'to-number' => self::toNumber(array_map($at, array_keys($args))),
            'to-string' => self::toString($at(0)),
            'to-boolean' => self::truthy($at(0)),
            'number', 'string', 'boolean' => $at(0),
            'coalesce' => self::coalesce($args, $at),
            'concat' => implode('', array_map(fn (int $i): string => self::toString($at($i)), array_keys($args))),
            'upcase' => strtoupper(self::toString($at(0))),
            'downcase' => strtolower(self::toString($at(0))),
            default => throw StargazerException::unsupportedStyleExpression($op),
        };
    }

    /** Whether $value is an expression: an array headed by an operator name. */
    public static function isExpression(mixed $value): bool
    {
        return is_array($value) && isset($value[0]) && is_string($value[0]) && in_array($value[0], self::OPERATORS, true);
    }

    public static function truthy(mixed $value): bool
    {
        return ! in_array($value, [null, false, 0, 0.0, ''], true);
    }

    private static function equal(mixed $a, mixed $b): bool
    {
        return is_numeric($a) && is_numeric($b) && ! is_string($a) && ! is_string($b) ? $a == $b : $a === $b;
    }

    private static function compare(string $op, mixed $a, mixed $b): bool
    {
        // Mixed or missing types compare false, as the GL runtime's typed comparisons do.
        if (is_null($a) || is_null($b) || (is_string($a) !== is_string($b))) {
            return false;
        }

        return match ($op) {
            '<' => $a < $b,
            '<=' => $a <= $b,
            '>' => $a > $b,
            '>=' => $a >= $b,
        };
    }

    private static function case(array $args, Closure $at): mixed
    {
        $last = count($args) - 1;
        for ($i = 0; $i < $last; $i += 2) {
            if (self::truthy($at($i))) {
                return $at($i + 1);
            }
        }

        return $at($last);
    }

    private static function match(array $args, Closure $at): mixed
    {
        $input = $at(0);
        $last = count($args) - 1;
        for ($i = 1; $i < $last; $i += 2) {
            foreach ((array) $args[$i] as $label) {
                if (self::equal($input, $label)) {
                    return $at($i + 1);
                }
            }
        }

        return $at($last);
    }

    private static function step(array $args, Closure $at): mixed
    {
        $input = (float) $at(0);
        $chosen = 1;
        for ($i = 2; $i + 1 < count($args); $i += 2) {
            if ($input >= (float) $args[$i]) {
                $chosen = $i + 1;
            }
        }

        return $at($chosen);
    }

    private static function interpolate(array $args, Closure $at): mixed
    {
        $kind = $args[0];
        $base = ($kind[0] ?? 'linear') === 'exponential' ? (float) ($kind[1] ?? 1) : 1.0;
        $input = (float) $at(1);
        $count = count($args);
        if ($input <= (float) $args[2]) {
            return $at(3);
        }
        for ($i = 2; $i + 3 < $count; $i += 2) {
            $low = (float) $args[$i];
            $high = (float) $args[$i + 2];
            if ($input <= $high) {
                $range = $high - $low;
                $t = $range == 0.0 ? 0.0 : ($base == 1.0 ? ($input - $low) / $range : ($base ** ($input - $low) - 1) / ($base ** $range - 1));

                return self::blend($at($i + 1), $at($i + 3), $t);
            }
        }

        return $at($count - 1);
    }

    private static function blend(mixed $from, mixed $to, float $t): mixed
    {
        if (is_numeric($from) && is_numeric($to)) {
            return $from + ($to - $from) * $t;
        }
        $a = GibsStyleColor::parse($from);
        $b = GibsStyleColor::parse($to);
        if (is_null($a) || is_null($b)) {
            return $t < 1.0 ? $from : $to;
        }

        return array_map(fn (float $x, float $y): float => $x + ($y - $x) * $t, $a, $b);
    }

    private static function coalesce(array $args, Closure $at): mixed
    {
        foreach (array_keys($args) as $i) {
            if (! is_null($value = $at($i))) {
                return $value;
            }
        }

        return null;
    }

    private static function toNumber(array $values): ?float
    {
        foreach ($values as $value) {
            if (is_numeric($value) || is_bool($value)) {
                return (float) $value;
            }
        }

        return null;
    }

    private static function toString(mixed $value): string
    {
        return match (true) {
            is_null($value) => '',
            is_bool($value) => $value ? 'true' : 'false',
            is_array($value) => json_encode($value),
            default => (string) $value,
        };
    }
}
