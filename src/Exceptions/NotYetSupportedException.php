<?php

namespace ProjectSaturnStudios\Stargazer\Exceptions;

class NotYetSupportedException extends StargazerException
{
    public static function forApi(string $name): self
    {
        return new self("{$name} is not yet supported by Stargazer.");
    }
}
