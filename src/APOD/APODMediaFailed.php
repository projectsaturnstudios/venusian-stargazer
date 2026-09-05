<?php

namespace ProjectSaturnStudios\Stargazer\APOD;

use ProjectSaturnStudios\Stargazer\APOD\DataObjects\AstronomyPicture;
use Voyager\Contracts\IOPools\Completion;
use Voyager\IOPools\DTO\HttpResult;

/**
 * The media download came back unusable. Carries the picture it was for
 * and the raw HttpResult so a listener can judge for itself.
 */
readonly class APODMediaFailed implements Completion
{
    public string $name;

    public function __construct(
        public AstronomyPicture $apod,
        public HttpResult $result,
        public string $reason,
    ) {
        $this->name = $result->name;
    }

    public function ok(): bool
    {
        return false;
    }
}
