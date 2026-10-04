<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Enums;

/** The two WMS versions GIBS speaks. 1.3.0 orders an EPSG:4326 box latitude first and names the CRS 'CRS'; 1.1.1 names it 'SRS'. */
enum WmsVersion: string
{
    case V1_1_1 = '1.1.1';
    case V1_3_0 = '1.3.0';
}
