<?php

use ProjectSaturnStudios\Stargazer\NasaClient;

if (! function_exists('nasa')) {
    /**
     * The NASA client bound as 'nasa'.
     */
    function nasa(): NasaClient
    {
        return app('nasa');
    }
}
