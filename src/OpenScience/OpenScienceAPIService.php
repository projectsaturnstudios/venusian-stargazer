<?php

namespace ProjectSaturnStudios\Stargazer\OpenScience;

use ProjectSaturnStudios\Stargazer\Exceptions\NotYetSupportedException;

class OpenScienceAPIService
{
    public function __construct()
    {
        throw NotYetSupportedException::forApi('Open Science Data Repository');
    }
}
