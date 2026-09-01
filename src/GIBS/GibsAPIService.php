<?php

namespace ProjectSaturnStudios\Stargazer\GIBS;

use ProjectSaturnStudios\Stargazer\Exceptions\NotYetSupportedException;

class GibsAPIService
{
    public function __construct()
    {
        throw NotYetSupportedException::forApi('GIBS');
    }
}
