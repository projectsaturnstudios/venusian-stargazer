<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Enums;

/** Which processing of the imagery an endpoint serves. */
enum GibsImagerySet: string
{
    /** Standard where it exists, near real time where it does not. */
    case BEST = 'best';

    case STANDARD = 'std';

    case NEAR_REAL_TIME = 'nrt';

    /** Both, each layer under its own identifier. */
    case ALL = 'all';
}
