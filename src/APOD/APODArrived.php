<?php

namespace ProjectSaturnStudios\Stargazer\APOD;

use Voyager\Contracts\IOPools\Completion;
use Voyager\IOPools\DTO\HttpResult;

readonly class APODArrived implements Completion
{
    public function __construct(
        public readonly string $name,
        public readonly array $apods,
    ) {}

    public function ok(): bool
    {
        return true;
    }
}
