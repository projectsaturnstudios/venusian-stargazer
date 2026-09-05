<?php

namespace ProjectSaturnStudios\Stargazer\NeoWs;

use Voyager\Contracts\IOPools\Completion;

/**
 * A NeoWs page landed, hydrated. $page is NeoFeed (feed),
 * NearEarthObject (lookup), or NeoBrowse (browse); the mail name
 * says which endpoint answered.
 */
readonly class NeowsArrived implements Completion
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
