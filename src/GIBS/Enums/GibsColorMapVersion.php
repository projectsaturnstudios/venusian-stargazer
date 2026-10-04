<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Enums;

/** The two colour map schemas GIBS publishes: v1.0, one map per file; v1.3, data and no-data maps with legends. */
enum GibsColorMapVersion: string
{
    case V1_0 = 'v1.0';
    case V1_3 = 'v1.3';
}
