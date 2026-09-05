<?php

namespace ProjectSaturnStudios\Stargazer\APOD;

use Voyager\Contracts\IOPools\Completion;
use Voyager\IOPools\DTO\HttpResult;

/**
 * An APOD call that came back unusable: transport failure, a non-2xx
 * status, or a body that was not the JSON we asked for. Carries the raw
 * HttpResult so a listener can judge for itself.
 */
readonly class APODFailed implements Completion
{
    public function __construct(
        public string $name,
        public HttpResult $result,
        public string $reason,
    ) {}

    public function ok(): bool
    {
        return false;
    }
}
