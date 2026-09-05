<?php

namespace ProjectSaturnStudios\Stargazer\EPIC;

use Voyager\Contracts\IOPools\Completion;
use Voyager\IOPools\DTO\HttpResult;

/**
 * An EPIC call that came back unusable. Carries the raw HttpResult so a
 * listener can judge for itself.
 */
readonly class EpicFailed implements Completion
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
