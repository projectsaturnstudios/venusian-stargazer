<?php

namespace ProjectSaturnStudios\Stargazer\Techport;

use ProjectSaturnStudios\Stargazer\Exceptions\NotYetSupportedException;

class TechportAPIService
{
    public function __construct()
    {
        throw NotYetSupportedException::forApi('Techport');
    }
}
