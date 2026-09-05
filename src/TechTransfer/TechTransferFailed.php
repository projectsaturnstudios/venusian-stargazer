<?php

namespace ProjectSaturnStudios\Stargazer\TechTransfer;

use Voyager\Contracts\IOPools\Completion;
use Voyager\IOPools\DTO\HttpResult;

/**
 * A TechTransfer call that came back unusable. Carries the raw HttpResult so
 * a listener can judge for itself.
 */
readonly class TechTransferFailed implements Completion
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
