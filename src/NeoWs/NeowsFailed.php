<?php

namespace ProjectSaturnStudios\Stargazer\NeoWs;

use Voyager\Contracts\IOPools\Completion;
use Voyager\IOPools\DTO\HttpResult;

/**
 * A NeoWs call that came back unusable. Carries the raw HttpResult so
 * a listener can judge for itself.
 */
readonly class NeowsFailed implements Completion
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
