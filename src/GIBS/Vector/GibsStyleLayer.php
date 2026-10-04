<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Vector;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;

/** One layer of a vector style: which source layer it draws, how (circle, line, fill, symbol), and with what. */
final readonly class GibsStyleLayer implements HydratesFromArray
{
    /** Properties whose plain value is itself an array, so an array is not an expression there. */
    private const array ARRAY_PROPERTIES = ['text-font', 'text-variable-anchor', 'line-dasharray', 'text-offset', 'icon-offset', 'circle-translate', 'line-translate', 'fill-translate'];

    /**
     * @param  array<string, mixed>  $paint
     * @param  array<string, mixed>  $layout
     */
    public function __construct(
        public string $id,
        public string $source,
        public string $sourceLayer,
        public string $type,
        public array $paint,
        public array $layout,
        public mixed $filter,
        public ?float $minzoom,
        public ?float $maxzoom,
        public ?string $sourceDescription,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            id: (string) ($data['id'] ?? ''),
            source: (string) ($data['source'] ?? ''),
            sourceLayer: (string) ($data['source-layer'] ?? ''),
            type: (string) ($data['type'] ?? ''),
            paint: (array) ($data['paint'] ?? []),
            layout: (array) ($data['layout'] ?? []),
            filter: $data['filter'] ?? null,
            minzoom: isset($data['minzoom']) ? (float) $data['minzoom'] : null,
            maxzoom: isset($data['maxzoom']) ? (float) $data['maxzoom'] : null,
            sourceDescription: isset($data['source-description']) ? (string) $data['source-description'] : null,
        );
    }

    /** Whether this layer draws $feature at $zoom: within its zoom range and passing its filter. */
    public function draws(GibsVectorFeature $feature, float $zoom): bool
    {
        if ((! is_null($this->minzoom) && $zoom < $this->minzoom) || (! is_null($this->maxzoom) && $zoom >= $this->maxzoom)) {
            return false;
        }

        return is_null($this->filter) || GibsStyleExpression::truthy(GibsStyleExpression::evaluate($this->filter, $zoom, $feature->properties(), $feature->type));
    }

    /** A paint property for $feature at $zoom; $default when the layer sets none. */
    public function paint(string $property, GibsVectorFeature $feature, float $zoom, mixed $default = null): mixed
    {
        return array_key_exists($property, $this->paint) ? $this->value($property, $this->paint[$property], $feature, $zoom) : $default;
    }

    /** A layout property for $feature at $zoom; $default when the layer sets none. */
    public function layout(string $property, GibsVectorFeature $feature, float $zoom, mixed $default = null): mixed
    {
        return array_key_exists($property, $this->layout) ? $this->value($property, $this->layout[$property], $feature, $zoom) : $default;
    }

    private function value(string $property, mixed $value, GibsVectorFeature $feature, float $zoom): mixed
    {
        if (in_array($property, self::ARRAY_PROPERTIES, true) && ! GibsStyleExpression::isExpression($value)) {
            return $value;
        }

        return GibsStyleExpression::evaluate($value, $zoom, $feature->properties(), $feature->type);
    }
}
