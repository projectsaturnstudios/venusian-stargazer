<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Vector;

/** A named layer of a vector tile: its features on a grid of $extent units a side. */
final readonly class GibsVectorLayer
{
    /**
     * @param  list<GibsVectorFeature>  $features
     */
    public function __construct(
        public string $name,
        public int $version,
        public int $extent,
        public array $features,
    ) {}
}
