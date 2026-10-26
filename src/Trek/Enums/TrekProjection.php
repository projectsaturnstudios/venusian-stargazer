<?php

namespace ProjectSaturnStudios\Stargazer\Trek\Enums;

/** The three ways Trek tiles a body. The value is the projection's folder under tiles/{Body}/. */
enum TrekProjection: string
{
    case EQUIRECTANGULAR = 'EQ';
    case NORTH_POLE = 'NP';
    case SOUTH_POLE = 'SP';

    /** The layer index's folder: eq for the global map, polar for either pole. */
    public function index(): string
    {
        return $this === self::EQUIRECTANGULAR ? 'eq' : 'polar';
    }
}
