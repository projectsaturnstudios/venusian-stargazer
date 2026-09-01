<?php

namespace ProjectSaturnStudios\Stargazer\EONET\Enums;

enum EonetEventStatus: string
{
    case OPEN = 'open';
    case CLOSED = 'closed';
    case ALL = 'all';
}
