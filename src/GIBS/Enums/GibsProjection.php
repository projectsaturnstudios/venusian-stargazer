<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Enums;

/** The four map projections GIBS serves every protocol in. */
enum GibsProjection: string
{
    /** Geographic latitude/longitude, degrees. */
    case EPSG4326 = 'epsg4326';

    /** Web Mercator, metres. */
    case EPSG3857 = 'epsg3857';

    /** NSIDC Sea Ice Polar Stereographic North, metres. */
    case EPSG3413 = 'epsg3413';

    /** Antarctic Polar Stereographic, metres. */
    case EPSG3031 = 'epsg3031';

    /** 'EPSG:4326' and so on, as WMS and TWMS name it. */
    public function crs(): string
    {
        return 'EPSG:'.substr($this->value, 4);
    }
}
