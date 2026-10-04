<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Wmts\DataObjects;

use ProjectSaturnStudios\Stargazer\GIBS\Support\GibsXml;
use SimpleXMLElement;

final readonly class WmtsStyle
{
    /**
     * @param  list<WmtsLegend>  $legends
     */
    public function __construct(
        public string $identifier,
        public string $title,
        public bool $isDefault,
        public array $legends,
    ) {}

    public static function fromXml(SimpleXMLElement $style): self
    {
        $ows = $style->children(GibsXml::OWS);
        $legends = [];
        foreach ($style->LegendURL as $legend) {
            $legends[] = WmtsLegend::fromXml($legend);
        }

        return new self((string) $ows->Identifier, (string) $ows->Title, (string) $style['isDefault'] === 'true', $legends);
    }

    /** The legend for 'horizontal' or 'vertical', or null. */
    public function legend(string $orientation): ?WmtsLegend
    {
        foreach ($this->legends as $legend) {
            if ($legend->orientation === $orientation) {
                return $legend;
            }
        }

        return null;
    }
}
