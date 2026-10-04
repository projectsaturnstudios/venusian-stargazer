<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Vector;

/** A vector tile feature's geometry kind, as the Mapbox Vector Tile spec numbers it. */
enum GibsGeometryType: int
{
    case UNKNOWN = 0;
    case POINT = 1;
    case LINESTRING = 2;
    case POLYGON = 3;

    /** The name style expressions' ['geometry-type'] answers. */
    public function styleName(): string
    {
        return match ($this) {
            self::POINT => 'Point',
            self::LINESTRING => 'LineString',
            self::POLYGON => 'Polygon',
            self::UNKNOWN => 'Unknown',
        };
    }
}
