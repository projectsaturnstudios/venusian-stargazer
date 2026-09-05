<?php

namespace ProjectSaturnStudios\Stargazer\DONKI;

use Voyager\Contracts\IOPools\Completion;

/**
 * A DONKI listing landed, hydrated. $items holds the endpoint's row DTOs
 * (Cme, Flare, Notification, …); the mail name says which endpoint answered.
 */
readonly class DonkiArrived implements Completion
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
