<?php

namespace ProjectSaturnStudios\Stargazer\DONKI;

use Voyager\Contracts\IOPools\Completion;
use Voyager\IOPools\DTO\HttpResult;

/**
 * A DONKI call that came back unusable. Carries the raw HttpResult so a
 * listener can judge for itself.
 */
readonly class DonkiFailed implements Completion
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
