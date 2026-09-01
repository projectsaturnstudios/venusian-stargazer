<?php

namespace ProjectSaturnStudios\Stargazer\Trek;

use ProjectSaturnStudios\Stargazer\Exceptions\NotYetSupportedException;

class TrekWmtsAPIService
{
    public function __construct()
    {
        throw NotYetSupportedException::forApi('Trek WMTS');
    }
}
