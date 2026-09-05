<?php

namespace ProjectSaturnStudios\Stargazer\InSight;

use Voyager\Contracts\IOPools\Completion;
use Voyager\IOPools\DTO\HttpResult;

/**
 * An InSight call that came back unusable. Carries the raw HttpResult so
 * a listener can judge for itself.
 */
readonly class InsightFailed implements Completion
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
