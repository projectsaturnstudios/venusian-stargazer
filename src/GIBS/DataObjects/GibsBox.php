<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\DataObjects;

use InvalidArgumentException;

/** A rectangle in a projection's own units: degrees for EPSG:4326, metres for the rest. x is east, y is north. */
final readonly class GibsBox
{
    public function __construct(
        public float $minX,
        public float $minY,
        public float $maxX,
        public float $maxY,
    ) {
        if (! ($minX <= $maxX && $minY <= $maxY)) {
            throw new InvalidArgumentException("A box runs min to max: got x {$minX}..{$maxX}, y {$minY}..{$maxY}.");
        }
    }

    public function width(): float
    {
        return $this->maxX - $this->minX;
    }

    public function height(): float
    {
        return $this->maxY - $this->minY;
    }

    /** 'minX,minY,maxX,maxY' as WMTS and WMS 1.1.1 take it. */
    public function toString(): string
    {
        return implode(',', array_map(self::number(...), [$this->minX, $this->minY, $this->maxX, $this->maxY]));
    }

    /** 'minY,minX,maxY,maxX': WMS 1.3.0 in EPSG:4326 takes latitude first. */
    public function toLatitudeFirstString(): string
    {
        return implode(',', array_map(self::number(...), [$this->minY, $this->minX, $this->maxY, $this->maxX]));
    }

    private static function number(float $value): string
    {
        return rtrim(rtrim(sprintf('%.10F', $value), '0'), '.');
    }
}
