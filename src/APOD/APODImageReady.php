<?php

namespace ProjectSaturnStudios\Stargazer\APOD;

/**
 * An image day's picture landed.
 */
readonly class APODImageReady extends APODMediaReady
{
    protected function fallbackExtension(): string
    {
        return 'jpg';
    }
}
