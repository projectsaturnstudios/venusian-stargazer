<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Wms\DataObjects;

use ProjectSaturnStudios\Stargazer\Exceptions\StargazerException;
use ProjectSaturnStudios\Stargazer\GIBS\Support\GibsXml;
use SimpleXMLElement;

/**
 * A WMS capabilities document, 1.1.1 or 1.3.0 (TWMS answers the 1.1.1 shape): its
 * GetMap formats and every named layer, groups included.
 */
final readonly class WmsCapabilities
{
    /**
     * @param  list<string>  $mapFormats  What GetMap lists; GIBS serves PNG, JPEG and TIFF of them.
     * @param  array<string, WmsLayer>  $layers  By name, in document order.
     */
    public function __construct(
        public string $version,
        public string $title,
        public array $mapFormats,
        public array $layers,
    ) {}

    /** @throws StargazerException When the text is not a capabilities document, or is an exception report. */
    public static function fromXml(string $xml, string $url = 'WMS capabilities'): self
    {
        $root = GibsXml::load($xml, $url);
        if (! in_array($root->getName(), ['WMS_Capabilities', 'WMT_MS_Capabilities'], true)) {
            throw StargazerException::invalidXml($url, "its root is <{$root->getName()}>, not WMS capabilities");
        }

        $service = GibsXml::first($root, 'Service');
        $capability = GibsXml::first($root, 'Capability');
        $getMap = is_null($capability) ? null : GibsXml::first(GibsXml::first($capability, 'Request') ?? $capability, 'GetMap');
        $formats = is_null($getMap) ? [] : array_map(fn (SimpleXMLElement $f): string => trim((string) $f), GibsXml::all($getMap, 'Format'));

        $layers = [];
        $walk = function (SimpleXMLElement $layer, ?string $group) use (&$walk, &$layers): void {
            $read = WmsLayer::fromXml($layer, $group);
            if ($read->name !== '') {
                $layers[$read->name] = $read;
            }
            foreach (GibsXml::all($layer, 'Layer') as $child) {
                $walk($child, $read->name !== '' ? $read->name : $group);
            }
        };
        foreach (is_null($capability) ? [] : GibsXml::all($capability, 'Layer') as $top) {
            $walk($top, null);
        }

        return new self((string) $root['version'], GibsXml::text($service ?? $root, 'Title') ?? '', $formats, $layers);
    }

    /** @throws StargazerException When no layer has that name. */
    public function layer(string $name): WmsLayer
    {
        return $this->layers[$name] ?? throw StargazerException::unknownGibsLayer($name);
    }
}
