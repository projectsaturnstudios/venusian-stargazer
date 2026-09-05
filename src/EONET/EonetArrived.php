<?php

namespace ProjectSaturnStudios\Stargazer\EONET;

use Voyager\Contracts\IOPools\Completion;

/**
 * An EONET page landed, hydrated. $page is the endpoint's page DTO —
 * EonetEventsPage, EonetCategoriesPage, EonetSourcesPage, EonetLayersPage,
 * or EonetMagnitudesPage; the mail name says which endpoint answered.
 */
readonly class EonetArrived implements Completion
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
