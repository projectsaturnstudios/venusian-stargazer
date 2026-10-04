<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Wmts\DataObjects;

use ProjectSaturnStudios\Stargazer\GIBS\DataObjects\GibsBox;
use ProjectSaturnStudios\Stargazer\GIBS\Support\GibsXml;
use SimpleXMLElement;

/**
 * One zoom level of a tile matrix set: the grid of tiles and where it sits.
 * Coordinates are the set's CRS units (degrees or metres), x east, y north;
 * rows count down from the top-left corner, columns right.
 */
final readonly class WmtsTileMatrix
{
    /** The pixel size OGC WMTS standardises scale on: 0.28 mm. */
    public const float PIXEL_METRES = 0.00028;

    public function __construct(
        public string $identifier,
        public float $scaleDenominator,
        public float $topLeftX,
        public float $topLeftY,
        public int $tileWidth,
        public int $tileHeight,
        public int $matrixWidth,
        public int $matrixHeight,
        public float $metresPerUnit,
    ) {}

    public static function fromXml(SimpleXMLElement $matrix, float $metresPerUnit): self
    {
        $ows = $matrix->children(GibsXml::OWS);
        [$x, $y] = GibsXml::corner((string) $matrix->TopLeftCorner);

        return new self(
            identifier: (string) $ows->Identifier,
            scaleDenominator: (float) $matrix->ScaleDenominator,
            topLeftX: $x,
            topLeftY: $y,
            tileWidth: (int) $matrix->TileWidth,
            tileHeight: (int) $matrix->TileHeight,
            matrixWidth: (int) $matrix->MatrixWidth,
            matrixHeight: (int) $matrix->MatrixHeight,
            metresPerUnit: $metresPerUnit,
        );
    }

    /** CRS units one pixel spans: scale × 0.28 mm ÷ metres per unit. */
    public function pixelSpan(): float
    {
        return $this->scaleDenominator * self::PIXEL_METRES / $this->metresPerUnit;
    }

    /** The area tile ($row, $col) covers. Edge tiles can reach past the CRS's own extent; GIBS pads them. */
    public function tileBox(int $row, int $col): GibsBox
    {
        $width = $this->tileWidth * $this->pixelSpan();
        $height = $this->tileHeight * $this->pixelSpan();
        $left = $this->topLeftX + $col * $width;
        $top = $this->topLeftY - $row * $height;

        return new GibsBox($left, $top - $height, $left + $width, $top);
    }

    /** The tiles $box touches, clipped to the grid; null when it touches none. */
    public function covering(GibsBox $box): ?WmtsTileRange
    {
        $width = $this->tileWidth * $this->pixelSpan();
        $height = $this->tileHeight * $this->pixelSpan();
        // A box edge exactly on a tile edge does not reach into the next tile.
        $firstCol = (int) floor(($box->minX - $this->topLeftX) / $width);
        $lastCol = (int) ceil(($box->maxX - $this->topLeftX) / $width) - 1;
        $firstRow = (int) floor(($this->topLeftY - $box->maxY) / $height);
        $lastRow = (int) ceil(($this->topLeftY - $box->minY) / $height) - 1;

        $firstCol = max($firstCol, 0);
        $firstRow = max($firstRow, 0);
        $lastCol = min($lastCol, $this->matrixWidth - 1);
        $lastRow = min($lastRow, $this->matrixHeight - 1);
        if ($firstCol > $lastCol || $firstRow > $lastRow) {
            return null;
        }

        return new WmtsTileRange($this->identifier, $firstRow, $lastRow, $firstCol, $lastCol);
    }
}
