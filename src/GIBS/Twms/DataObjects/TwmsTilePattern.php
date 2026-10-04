<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Twms\DataObjects;

use ProjectSaturnStudios\Stargazer\GIBS\DataObjects\GibsBox;

/**
 * One TilePattern: the GetMap query for one tile of the group's fixed grid, in
 * a form with ${time} and one without. TWMS answers only these exact boxes.
 */
final readonly class TwmsTilePattern
{
    public function __construct(
        public ?string $timed,
        public ?string $timeless,
        public GibsBox $box,
        public int $width,
        public int $height,
    ) {}

    /** The pattern's lines (GIBS writes the timed one first). */
    public static function fromText(string $text, string $key): self
    {
        $timed = null;
        $timeless = null;
        foreach (preg_split('/\s+/', trim($text)) as $line) {
            if ($line === '') {
                continue;
            }
            str_contains($line, $key) ? $timed ??= $line : $timeless ??= $line;
        }
        parse_str($timeless ?? $timed ?? '', $query);
        $box = array_map('floatval', explode(',', (string) ($query['bbox'] ?? '0,0,0,0')));

        return new self($timed, $timeless, new GibsBox(...array_pad($box, 4, 0.0)), (int) ($query['width'] ?? 0), (int) ($query['height'] ?? 0));
    }

    /** The query for this tile: the timed form with $time put in, else the timeless form. */
    public function query(?string $time, string $key = '${time}'): string
    {
        if (! is_null($time) && ! is_null($this->timed)) {
            return str_replace($key, rawurlencode($time), $this->timed);
        }

        return $this->timeless ?? str_replace('&time='.$key, '', (string) $this->timed);
    }
}
