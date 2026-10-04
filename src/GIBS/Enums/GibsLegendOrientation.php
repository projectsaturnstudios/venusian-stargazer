<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Enums;

/** Which way a legend file runs; GIBS names its files with the letter. */
enum GibsLegendOrientation: string
{
    case HORIZONTAL = 'H';
    case VERTICAL = 'V';
}
