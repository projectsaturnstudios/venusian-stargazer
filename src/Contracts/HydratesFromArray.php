<?php

namespace ProjectSaturnStudios\Stargazer\Contracts;

interface HydratesFromArray
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): static;
}
