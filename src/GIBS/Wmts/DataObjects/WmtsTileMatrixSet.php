<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Wmts\DataObjects;

use ProjectSaturnStudios\Stargazer\GIBS\Support\GibsXml;
use SimpleXMLElement;

/** A named pyramid of tile matrices in one CRS ('250m', 'GoogleMapsCompatible_Level9', …). */
final readonly class WmtsTileMatrixSet
{
    /** Metres in one degree on the WGS 84 equator: 2π × 6378137 ÷ 360. */
    public const float METRES_PER_DEGREE = 111319.49079327357;

    /**
     * @param  list<WmtsTileMatrix>  $matrices  Zoom 0 first.
     */
    public function __construct(
        public string $identifier,
        public string $crs,
        public array $matrices,
    ) {}

    public static function fromXml(SimpleXMLElement $set): self
    {
        $ows = $set->children(GibsXml::OWS);
        $crs = (string) $ows->SupportedCRS;
        $perUnit = self::inDegrees($crs) ? self::METRES_PER_DEGREE : 1.0;
        $matrices = [];
        foreach ($set->TileMatrix as $matrix) {
            $matrices[] = WmtsTileMatrix::fromXml($matrix, $perUnit);
        }

        return new self((string) $ows->Identifier, $crs, $matrices);
    }

    /** Whether the CRS counts in degrees (CRS84, EPSG:4326) rather than metres. */
    public static function inDegrees(string $crs): bool
    {
        return str_contains($crs, 'CRS84') || preg_match('/EPSG:+4326$/', $crs) === 1;
    }

    public function zoomLevels(): int
    {
        return count($this->matrices);
    }

    /** The matrix for zoom $level, or null past the deepest. */
    public function matrix(int $level): ?WmtsTileMatrix
    {
        return $this->matrices[$level] ?? null;
    }

    /** The shallowest matrix whose pixels are no larger than $span CRS units, else the deepest. */
    public function matrixFor(float $span): WmtsTileMatrix
    {
        foreach ($this->matrices as $matrix) {
            if ($matrix->pixelSpan() <= $span) {
                return $matrix;
            }
        }

        return $this->matrices[array_key_last($this->matrices)];
    }
}
