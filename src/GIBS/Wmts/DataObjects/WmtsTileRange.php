<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Wmts\DataObjects;

/** A rectangle of tiles in one matrix, rows and columns inclusive. */
final readonly class WmtsTileRange
{
    public function __construct(
        public string $matrix,
        public int $firstRow,
        public int $lastRow,
        public int $firstCol,
        public int $lastCol,
    ) {}

    public function count(): int
    {
        return ($this->lastRow - $this->firstRow + 1) * ($this->lastCol - $this->firstCol + 1);
    }

    /** @return list<array{int, int}> [row, col], row by row. */
    public function tiles(): array
    {
        $tiles = [];
        for ($row = $this->firstRow; $row <= $this->lastRow; $row++) {
            for ($col = $this->firstCol; $col <= $this->lastCol; $col++) {
                $tiles[] = [$row, $col];
            }
        }

        return $tiles;
    }
}
