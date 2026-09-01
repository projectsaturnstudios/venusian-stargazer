<?php

namespace ProjectSaturnStudios\Stargazer\SSD;

use ProjectSaturnStudios\Stargazer\Exceptions\NotYetSupportedException;

class SsdCneosAPIService
{
    public function __construct()
    {
        throw NotYetSupportedException::forApi('SSD/CNEOS');
    }
}
