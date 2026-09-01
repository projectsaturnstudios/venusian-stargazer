<?php

namespace ProjectSaturnStudios\Stargazer\SSC;

use ProjectSaturnStudios\Stargazer\Exceptions\NotYetSupportedException;

class SscAPIService
{
    public function __construct()
    {
        throw NotYetSupportedException::forApi('Satellite Situation Center');
    }
}
