<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Enums;

/** The two URL shapes WMTS takes: path segments (RESTful) or a key-value query on wmts.cgi. */
enum GibsRequestStyle
{
    case REST;
    case KVP;
}
