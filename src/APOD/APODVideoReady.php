<?php

namespace ProjectSaturnStudios\Stargazer\APOD;

/**
 * A video day's media file landed.
 */
readonly class APODVideoReady extends APODMediaReady
{
    protected function fallbackExtension(): string
    {
        return 'mp4';
    }
}
