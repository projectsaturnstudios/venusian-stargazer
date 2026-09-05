<?php

namespace ProjectSaturnStudios\Stargazer\MagicAliases;

use ProjectSaturnStudios\Stargazer\APOD\ApodAPIService;
use ProjectSaturnStudios\Stargazer\DONKI\DonkiAPIService;
use ProjectSaturnStudios\Stargazer\EONET\EonetAPIService;
use ProjectSaturnStudios\Stargazer\EPIC\EpicAPIService;
use ProjectSaturnStudios\Stargazer\ImageLibrary\ImageLibraryAPIService;
use ProjectSaturnStudios\Stargazer\InSight\InsightAPIService;
use ProjectSaturnStudios\Stargazer\NasaClient;
use ProjectSaturnStudios\Stargazer\NeoWs\NeowsAPIService;
use ProjectSaturnStudios\Stargazer\TLE\TleAPIService;
use ProjectSaturnStudios\Stargazer\TechTransfer\TechTransferAPIService;
use Voyager\MagicAliases\MagicAlias;

/**
 * @method static EonetAPIService eonet()
 * @method static ApodAPIService apod()
 * @method static EpicAPIService epic()
 * @method static InsightAPIService insight()
 * @method static TleAPIService tle()
 * @method static TechTransferAPIService techtransfer()
 * @method static DonkiAPIService donki()
 * @method static NeowsAPIService neows()
 * @method static ImageLibraryAPIService imageLibrary()
 * @see NasaClient
 */
class NASA extends MagicAlias
{
    protected static function getMagicAliasAccessor(): string
    {
        return 'nasa';
    }
}
