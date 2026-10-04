<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Enums;

/** The legend files GIBS serves for each colour-mapped layer. */
enum GibsLegendFormat: string
{
    case SVG = 'svg';
    case PNG = 'png';
}
