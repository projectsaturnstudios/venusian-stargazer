<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Metadata;

use SimpleXMLElement;

/** One swatch of a v1.3 colour map's legend. */
final readonly class GibsLegendEntry
{
    /**
     * @param  array{int, int, int}  $rgb
     */
    public function __construct(
        public array $rgb,
        public string $tooltip,
        public string $id,
        public ?string $label,
        public bool $showTick,
        public bool $showLabel,
    ) {}

    public static function fromXml(SimpleXMLElement $entry): self
    {
        $rgb = array_map('intval', explode(',', (string) $entry['rgb']));

        return new self(
            rgb: [$rgb[0] ?? 0, $rgb[1] ?? 0, $rgb[2] ?? 0],
            tooltip: (string) $entry['tooltip'],
            id: (string) $entry['id'],
            label: isset($entry['label']) ? (string) $entry['label'] : null,
            showTick: (string) $entry['showTick'] === 'true',
            showLabel: (string) $entry['showLabel'] === 'true',
        );
    }
}
