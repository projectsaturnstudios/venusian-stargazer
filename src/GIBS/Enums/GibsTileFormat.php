<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Enums;

/** The formats GIBS tiles come in. */
enum GibsTileFormat: string
{
    case JPEG = 'image/jpeg';
    case PNG = 'image/png';
    case MVT = 'application/vnd.mapbox-vector-tile';

    /** The file extension a RESTful tile URL ends with. */
    public function extension(): string
    {
        return match ($this) {
            self::JPEG => 'jpeg',
            self::PNG => 'png',
            self::MVT => 'mvt',
        };
    }
}
