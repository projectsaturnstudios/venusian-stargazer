<?php

namespace ProjectSaturnStudios\Stargazer\TechTransfer;

use ProjectSaturnStudios\Stargazer\TechTransfer\DataObjects\TechTransferPage;
use Voyager\Contracts\IOPools\Completion;

/**
 * A TechTransfer catalog page landed, hydrated. $page is the
 * TechTransferPage for patent, software, or spinoff; the mail name
 * says which catalog answered.
 */
readonly class TechTransferArrived implements Completion
{
    public function __construct(
        public string $name,
        public TechTransferPage $page,
    ) {}

    public function ok(): bool
    {
        return true;
    }
}
