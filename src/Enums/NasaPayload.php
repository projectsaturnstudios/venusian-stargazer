<?php

namespace ProjectSaturnStudios\Stargazer\Enums;

/** What a request's hydrator receives: decoded JSON, the XML text, or the Http Response. */
enum NasaPayload
{
    case JSON;
    case XML;
    case BYTES;
}
