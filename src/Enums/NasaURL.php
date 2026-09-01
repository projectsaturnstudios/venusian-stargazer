<?php

namespace ProjectSaturnStudios\Stargazer\Enums;

enum NasaURL: string
{
    case APOD = 'https://api.nasa.gov/planetary/apod';
    case NEOWS = 'https://api.nasa.gov/neo/rest/v1';
    case DONKI = 'https://api.nasa.gov/DONKI';
    case EONET = 'https://eonet.gsfc.nasa.gov/api/v3';
    case EPIC = 'https://api.nasa.gov/EPIC';
    case INSIGHT = 'https://api.nasa.gov/insight_weather';
    case TECHTRANSFER = 'https://api.nasa.gov/techtransfer';
    case TLE = 'https://tle.ivanstanojevic.me/api';
    case IMAGE_LIBRARY = 'https://images-api.nasa.gov';
    case GIBS = 'https://gibs.earthdata.nasa.gov';
    case TREK_WMTS = 'https://trek.nasa.gov';
    case EXOPLANET = 'https://exoplanetarchive.ipac.caltech.edu/TAP';
    case OPEN_SCIENCE = 'https://osdr.nasa.gov';
    case SATELLITE_SITUATION_CENTER = 'https://sscweb.gsfc.nasa.gov/WS/sscr/2';
    case SSD_CNEOS = 'https://ssd-api.jpl.nasa.gov';
    case TECHPORT = 'https://api.nasa.gov/techport/api';
}
