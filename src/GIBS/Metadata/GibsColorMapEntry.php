<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Metadata;

use SimpleXMLElement;

/**
 * One colour of a colour map: the data range it stands for ('[0,0.005)',
 * '[310,+INF)'), the raw sample range it came from (v1.3), and its label.
 */
final readonly class GibsColorMapEntry
{
    /**
     * @param  array{int, int, int}  $rgb
     */
    public function __construct(
        public array $rgb,
        public bool $transparent,
        public bool $noData,
        public ?string $value,
        public ?string $sourceValue,
        public ?string $label,
        public ?string $ref,
    ) {}

    public static function fromXml(SimpleXMLElement $entry): self
    {
        $rgb = array_map('intval', explode(',', (string) $entry['rgb']));

        return new self(
            rgb: [$rgb[0] ?? 0, $rgb[1] ?? 0, $rgb[2] ?? 0],
            transparent: (string) $entry['transparent'] === 'true',
            noData: (string) $entry['nodata'] === 'true',
            value: isset($entry['value']) ? (string) $entry['value'] : null,
            sourceValue: isset($entry['sourceValue']) ? (string) $entry['sourceValue'] : null,
            label: isset($entry['label']) ? (string) $entry['label'] : null,
            ref: isset($entry['ref']) ? (string) $entry['ref'] : null,
        );
    }

    /** '#rrggbb'. */
    public function hex(): string
    {
        return vsprintf('#%02x%02x%02x', $this->rgb);
    }

    /** Whether $value falls in this entry's data range: '[' and ']' include an end, '(' and ')' do not; ±INF is open. */
    public function covers(float $value): bool
    {
        if (is_null($this->value) || ! preg_match('/^([\[(])\s*([^,]+?)\s*,\s*([^\])]+?)\s*([\])])$/', $this->value, $m)) {
            return false;
        }
        $low = self::bound($m[2]);
        $high = self::bound($m[3]);
        if ($low === $high) {
            return $m[1] === '[' && $m[4] === ']' && $value === $low;
        }

        return ($m[1] === '[' ? $value >= $low : $value > $low) && ($m[4] === ']' ? $value <= $high : $value < $high);
    }

    private static function bound(string $text): float
    {
        return match (strtoupper($text)) {
            '-INF' => -INF,
            '+INF', 'INF' => INF,
            default => (float) $text,
        };
    }
}
