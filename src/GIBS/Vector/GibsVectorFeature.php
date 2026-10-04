<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Vector;

use ProjectSaturnStudios\Stargazer\Exceptions\StargazerException;

/**
 * One feature of a vector tile layer. Geometry is in tile coordinates, 0 to
 * the layer's extent, x right and y down: points one per part, lines one per
 * part, polygons as their rings. A feature read from a tile keeps its tags and
 * geometry encoded and decodes them on each call to properties() or parts();
 * hold on to what those answer when reading it more than once.
 */
final readonly class GibsVectorFeature
{
    /**
     * @param  array<string, string|int|float|bool|null>|string  $properties  The properties, or packed tags read through $dictionary.
     * @param  list<list<array{int, int}>>|string  $parts  The parts, or packed geometry commands.
     */
    public function __construct(
        public ?int $id,
        public GibsGeometryType $type,
        private array|string $properties,
        private array|string $parts,
        private ?GibsVectorDictionary $dictionary = null,
    ) {}

    /**
     * @return array<string, string|int|float|bool|null>
     * @throws StargazerException When an encoded tag names a key or value its layer lacks.
     */
    public function properties(): array
    {
        if (is_array($this->properties)) {
            return $this->properties;
        }

        return ($this->dictionary ?? new GibsVectorDictionary([], []))->properties($this->properties);
    }

    /**
     * @return list<list<array{int, int}>>
     * @throws StargazerException When the encoded geometry runs past its end.
     */
    public function parts(): array
    {
        return is_array($this->parts) ? $this->parts : GibsVectorTile::geometry($this->parts, $this->type);
    }

    /**
     * A polygon's rings grouped into polygons: each exterior ring (positive
     * area with y down, as the spec winds them) followed by its holes.
     *
     * @return list<list<list<array{int, int}>>>
     */
    public function polygons(): array
    {
        $polygons = [];
        foreach ($this->parts() as $ring) {
            $area = self::area($ring);
            if ($area > 0 || $polygons === []) {
                $polygons[] = [$ring];
            } elseif ($area < 0) {
                $polygons[array_key_last($polygons)][] = $ring;
            }
        }

        return $polygons;
    }

    /** Twice the signed area by the shoelace sum; positive for a ring wound clockwise on screen. */
    public static function area(array $ring): int
    {
        $sum = 0;
        $count = count($ring);
        for ($i = 0; $i < $count; $i++) {
            [$x0, $y0] = $ring[$i];
            [$x1, $y1] = $ring[($i + 1) % $count];
            $sum += $x0 * $y1 - $x1 * $y0;
        }

        return $sum;
    }
}
