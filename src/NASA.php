<?php

namespace ProjectSaturnStudios\Stargazer;

use ProjectSaturnStudios\Stargazer\APOD\ApodAPIService;
use Voyager\MagicAliases\MagicAlias;

/**
 * @method static ApodAPIService apod()
 * @see NasaClient
 */
class NASA extends MagicAlias
{
    protected static function getMagicAliasAccessor(): string
    {
        return NasaClient::class;
    }
}
