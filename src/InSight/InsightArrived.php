<?php

namespace ProjectSaturnStudios\Stargazer\InSight;

use ProjectSaturnStudios\Stargazer\InSight\DataObjects\InsightWeather;
use Voyager\Contracts\IOPools\Completion;

/**
 * The Mars InSight weather feed landed, hydrated.
 */
readonly class InsightArrived implements Completion
{
    public function __construct(
        public string $name,
        public InsightWeather $weather,
    ) {}

    public function ok(): bool
    {
        return true;
    }
}
