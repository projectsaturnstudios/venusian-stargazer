<?php

namespace ProjectSaturnStudios\Stargazer\EPIC;

use Voyager\Contracts\IOPools\Completion;

/**
 * An EPIC listing landed, hydrated. $items holds EpicImage rows for the
 * imagery endpoints and EpicAvailableDate rows for the available ones;
 * the mail name says which endpoint answered.
 */
readonly class EpicArrived implements Completion
{
    /**
     * @param  list<object>  $items
     */
    public function __construct(
        public string $name,
        public array $items,
    ) {}

    public function ok(): bool
    {
        return true;
    }
}
