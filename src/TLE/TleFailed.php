<?php

namespace ProjectSaturnStudios\Stargazer\TLE;

use Voyager\Contracts\IOPools\Completion;
use Voyager\IOPools\DTO\HttpResult;

/**
 * A TLE call that came back unusable. Carries the raw HttpResult so
 * a listener can judge for itself.
 */
readonly class TleFailed implements Completion
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
