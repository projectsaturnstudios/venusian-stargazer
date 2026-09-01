<?php

namespace ProjectSaturnStudios\Stargazer;

use Closure;
use ProjectSaturnStudios\Stargazer\APOD\ApodAPIService;
use ProjectSaturnStudios\Stargazer\DONKI\DonkiAPIService;
use ProjectSaturnStudios\Stargazer\EONET\EonetAPIService;
use ProjectSaturnStudios\Stargazer\EPIC\EpicAPIService;
use ProjectSaturnStudios\Stargazer\Enums\NasaURL;
use ProjectSaturnStudios\Stargazer\Exoplanet\ExoplanetArchive;
use ProjectSaturnStudios\Stargazer\GIBS\GibsAPIService;
use ProjectSaturnStudios\Stargazer\ImageLibrary\ImageLibraryAPIService;
use ProjectSaturnStudios\Stargazer\InSight\InsightAPIService;
use ProjectSaturnStudios\Stargazer\NeoWs\NeowsAPIService;
use ProjectSaturnStudios\Stargazer\OpenScience\OpenScienceAPIService;
use ProjectSaturnStudios\Stargazer\SSC\SscAPIService;
use ProjectSaturnStudios\Stargazer\SSD\SsdCneosAPIService;
use ProjectSaturnStudios\Stargazer\TLE\TleAPIService;
use ProjectSaturnStudios\Stargazer\TechTransfer\TechTransferAPIService;
use ProjectSaturnStudios\Stargazer\Techport\TechportAPIService;
use ProjectSaturnStudios\Stargazer\Trek\TrekWmtsAPIService;
use Voyager\Http\Client\Factory;
use Voyager\IOPools\HttpPool;

class NasaClient
{
    public function __construct(
        protected ?string $api_key = null,
        protected ?Factory $http = null,
        protected ?HttpPool $pool = null,
    ) {}

    /**
     * @param  Closure(mixed):mixed|class-string  $hydrator
     * @param  array<string, mixed>  $query
     */
    public function pending(
        NasaURL $base,
        string $path,
        string $call_name,
        Closure|string $hydrator,
        array $query = [],
    ): PendingNasaRequest {
        return new PendingNasaRequest(
            base: $base,
            path: $path,
            call_name: $call_name,
            hydrator: $hydrator,
            query: $query,
            api_key: $this->api_key,
            http: $this->http,
            pool: $this->pool,
        );
    }

    public function donki(): DonkiAPIService
    {
        return new DonkiAPIService($this);
    }

    public function neows(): NeowsAPIService
    {
        return new NeowsAPIService($this);
    }

    public function eonet(): EonetAPIService
    {
        return new EonetAPIService($this);
    }

    public function apod(): ApodAPIService
    {
        return new ApodAPIService($this);
    }

    public function epic(): EpicAPIService
    {
        return new EpicAPIService($this);
    }

    public function insight(): InsightAPIService
    {
        return new InsightAPIService($this);
    }

    public function tle(): TleAPIService
    {
        return new TleAPIService($this);
    }

    public function techtransfer(): TechTransferAPIService
    {
        return new TechTransferAPIService($this);
    }

    public function imageLibrary(): ImageLibraryAPIService
    {
        return new ImageLibraryAPIService($this);
    }

    public function gibs(): GibsAPIService
    {
        return new GibsAPIService;
    }

    public function trek(): TrekWmtsAPIService
    {
        return new TrekWmtsAPIService;
    }

    public function exoplanet(): ExoplanetArchive
    {
        return new ExoplanetArchive;
    }

    public function openScience(): OpenScienceAPIService
    {
        return new OpenScienceAPIService;
    }

    public function ssc(): SscAPIService
    {
        return new SscAPIService;
    }

    public function ssd(): SsdCneosAPIService
    {
        return new SsdCneosAPIService;
    }

    public function techport(): TechportAPIService
    {
        return new TechportAPIService;
    }
}
