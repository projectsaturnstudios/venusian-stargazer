<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\DataObjects;

use DateTimeImmutable;
use DateTimeZone;

/**
 * One ISO 8601 entry of a time dimension: a single instant ('2020-01-01'), or
 * 'start/end/period' (every period from start to end, inclusive).
 */
final readonly class GibsTimePeriod
{
    public function __construct(
        public string $start,
        public string $end,
        public ?string $period,
    ) {}

    public static function parse(string $entry): self
    {
        $parts = explode('/', trim($entry));

        return new self($parts[0], $parts[1] ?? $parts[0], $parts[2] ?? null);
    }

    /** Whether $when (a date or date-time) is one of this entry's instants: within start..end and on a step from start. */
    public function includes(string $when): bool
    {
        $at = self::instant($when);
        $start = self::instant($this->start);
        if ($at < $start || $at > self::instant($this->end)) {
            return false;
        }
        if (is_null($this->period)) {
            return $at == $start;
        }
        if (! preg_match('/^P(?:(\d+)Y)?(?:(\d+)M)?(?:(\d+)D)?(?:T(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?)?$/', $this->period, $m)) {
            return false;
        }
        [$years, $months, $days, $hours, $minutes, $seconds] = array_map(fn (int $i): int => (int) ($m[$i] ?? 0), range(1, 6));
        $calendar = $years * 12 + $months;
        $clock = $days * 86400 + $hours * 3600 + $minutes * 60 + $seconds;
        if ($calendar === 0 && $clock === 0) {
            return $at == $start;
        }

        if ($calendar === 0) {
            return ($at->getTimestamp() - $start->getTimestamp()) % $clock === 0;
        }
        if ($clock === 0) {
            $apart = ((int) $at->format('Y') * 12 + (int) $at->format('n')) - ((int) $start->format('Y') * 12 + (int) $start->format('n'));

            return $apart % $calendar === 0 && $at->format('d H:i:s') === $start->format('d H:i:s');
        }

        // A period mixing months and days: step it out.
        for ($step = $start; $step <= $at; $step = $step->modify("+{$calendar} months +{$clock} seconds")) {
            if ($step == $at) {
                return true;
            }
        }

        return false;
    }

    private static function instant(string $when): DateTimeImmutable
    {
        return new DateTimeImmutable($when, new DateTimeZone('UTC'));
    }
}
