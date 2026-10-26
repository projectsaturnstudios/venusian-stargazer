<?php

namespace ProjectSaturnStudios\Stargazer\Trek\Enums;

/** A body Trek maps. The value is its folder in Trek's service URLs. */
enum TrekBody: string
{
    case MOON = 'moon';
    case MARS = 'mars';
    case VESTA = 'vesta';

    /** Its folder under tiles/. */
    public function folder(): string
    {
        return ucfirst($this->value);
    }

    /** The CRS Trek names for $projection, as its own API page lists them. */
    public function crs(TrekProjection $projection): string
    {
        return match ($this) {
            self::MOON => match ($projection) {
                TrekProjection::EQUIRECTANGULAR => 'urn:ogc:def:crs:EPSG::104903',
                TrekProjection::NORTH_POLE => 'urn:ogc:def:crs:IAU2000::30118',
                TrekProjection::SOUTH_POLE => 'urn:ogc:def:crs:IAU2000::30120',
            },
            self::MARS => match ($projection) {
                TrekProjection::EQUIRECTANGULAR => 'urn:ogc:def:crs:IAU2000::104905',
                TrekProjection::NORTH_POLE => 'urn:ogc:def:crs:IAU2000::49918',
                TrekProjection::SOUTH_POLE => 'urn:ogc:def:crs:IAU2000::49920',
            },
            self::VESTA => match ($projection) {
                TrekProjection::EQUIRECTANGULAR => 'urn:ogc:def:crs:EPSG::2000004Ω',
                TrekProjection::NORTH_POLE => 'urn:ogc:def:crs:trek::vesta_np',
                TrekProjection::SOUTH_POLE => 'urn:ogc:def:crs:trek::vesta_sp',
            },
        };
    }

    /** Whether its layer list is listVisibleLayers rather than a faceted searchItems. */
    public function listsVisibleLayers(): bool
    {
        return $this === self::VESTA;
    }
}
