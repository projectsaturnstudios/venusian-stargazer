<?php

namespace ProjectSaturnStudios\Stargazer\TLE;

use Voyager\Contracts\IOPools\Completion;

/**
 * A TLE page landed, hydrated. $page is TleCollection (collection/search)
 * or TleRecord (satellite); the mail name says which endpoint answered.
 */
readonly class TleArrived implements Completion
{
    public function __construct(
        public string $name,
        public object $page,
    ) {}

    public function ok(): bool
    {
        return true;
    }
}
