<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Enums;

/** The formats GIBS's WMS GetMap answers in; it refuses the others its capabilities list. */
enum GibsMapFormat: string
{
    case PNG = 'image/png';
    case JPEG = 'image/jpeg';
    case TIFF = 'image/tiff';
}
