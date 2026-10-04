<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Vector;

use Generator;
use ProjectSaturnStudios\Stargazer\Exceptions\StargazerException;

/**
 * A Mapbox Vector Tile (spec 2.1), read from its protobuf bytes. GIBS sends
 * its tiles gzipped inside the body; those are inflated first. Layers and
 * features are read here; each feature keeps its tags and geometry encoded
 * until asked (GibsVectorFeature::properties(), parts()), so a tile holds
 * about its own size: decoded whole, a 26,642-feature fire tile held 39 MB.
 */
final readonly class GibsVectorTile
{
    /**
     * @param  array<string, GibsVectorLayer>  $layers  By name.
     */
    public function __construct(public array $layers) {}

    /** @throws StargazerException When the bytes are not a vector tile. */
    public static function fromBytes(string $bytes): self
    {
        if (str_starts_with($bytes, "\x1f\x8b")) {
            // zlib reports damage as a warning; it becomes the exception instead.
            set_error_handler(fn (): bool => true);
            try {
                $inflated = gzdecode($bytes);
            } finally {
                restore_error_handler();
            }
            if ($inflated === false) {
                throw StargazerException::invalidVectorTile('its gzip wrapping is damaged.');
            }
            $bytes = $inflated;
        }

        $layers = [];
        foreach (self::fields($bytes) as [$field, $value]) {
            if ($field === 3) {
                $layer = self::readLayer($value);
                $layers[$layer->name] = $layer;
            }
        }

        return new self($layers);
    }

    public function layer(string $name): ?GibsVectorLayer
    {
        return $this->layers[$name] ?? null;
    }

    private static function readLayer(string $bytes): GibsVectorLayer
    {
        $name = '';
        $version = 1;
        $extent = 4096;
        $keys = [];
        $values = [];
        $features = [];
        foreach (self::fields($bytes) as [$field, $value]) {
            match ($field) {
                1 => $name = $value,
                2 => $features[] = $value,
                3 => $keys[] = $value,
                4 => $values[] = self::value($value),
                5 => $extent = $value,
                15 => $version = $value,
                default => null,
            };
        }

        $dictionary = new GibsVectorDictionary($keys, $values);

        return new GibsVectorLayer($name, $version, $extent, array_map(fn (string $feature): GibsVectorFeature => self::readFeature($feature, $dictionary), $features));
    }

    private static function readFeature(string $bytes, GibsVectorDictionary $dictionary): GibsVectorFeature
    {
        $id = null;
        $type = GibsGeometryType::UNKNOWN;
        $tags = '';
        $geometry = '';
        foreach (self::fields($bytes) as [$field, $value]) {
            // Packed runs join as they are; a writer's unpacked ints are packed here so every feature reads alike.
            match ($field) {
                1 => $id = $value,
                2 => $tags .= is_string($value) ? $value : self::encode($value),
                3 => $type = GibsGeometryType::tryFrom($value) ?? GibsGeometryType::UNKNOWN,
                4 => $geometry .= is_string($value) ? $value : self::encode($value),
                default => null,
            };
        }

        return new GibsVectorFeature($id, $type, $tags, $geometry, $dictionary);
    }

    /**
     * Commands into parts: MoveTo starts one (a point, or a line or ring's first
     * vertex), LineTo extends it, ClosePath ends a ring. Deltas are zigzag. A
     * LineTo that does not move adds no vertex: it adds nothing to the shape,
     * and GIBS's low-zoom tiles are mostly such steps (a 2.7 MB reservoirs tile,
     * 93% of its 1.3 million vertices).
     *
     * @internal GibsVectorFeature::parts() reads its geometry through this.
     *
     * @return list<list<array{int, int}>>
     */
    public static function geometry(string $packed, GibsGeometryType $type): array
    {
        $parts = [];
        $x = 0;
        $y = 0;
        $id = 0;
        $left = 0;      // vertices the current command still has
        $dx = null;     // a vertex half read
        foreach (self::varints($packed) as $value) {
            if ($left === 0) {
                $id = $value & 0x7;
                $left = $value >> 3;
                if ($id === 7) {
                    $left = 0;     // ClosePath: the ring closes on its first vertex.
                } elseif ($id !== 1 && $id !== 2) {
                    throw StargazerException::invalidVectorTile("its geometry has command {$id}.");
                }

                continue;
            }
            if (is_null($dx)) {
                $dx = $value;

                continue;
            }
            $dy = $value;
            $left--;
            if ($id === 2 && $dx === 0 && $dy === 0) {
                $dx = null;

                continue;
            }
            $x += ($dx >> 1) ^ -($dx & 1);
            $y += ($dy >> 1) ^ -($dy & 1);
            $dx = null;
            if ($id === 1) {
                $parts[] = [[$x, $y]];
            } else {
                $parts[array_key_last($parts) ?? 0][] = [$x, $y];
            }
        }
        if ($left !== 0 || ! is_null($dx)) {
            throw StargazerException::invalidVectorTile("its geometry has command {$id} with {$left} vertices past its end.");
        }

        return $type === GibsGeometryType::POINT ? array_map(fn (array $part): array => [$part[0]], $parts) : $parts;
    }

    private static function value(string $bytes): string|int|float|bool|null
    {
        foreach (self::fields($bytes) as [$field, $value]) {
            return match ($field) {
                1 => $value,
                2 => unpack('g', $value)[1],
                3 => unpack('e', $value)[1],
                4, 5 => $value,
                6 => ($value >> 1) ^ -($value & 1),
                7 => $value !== 0,
                default => null,
            };
        }

        return null;
    }

    /**
     * A message's fields in order: [number, value], the value an int (varint),
     * a string (length-delimited), or 4 or 8 raw bytes (fixed32, fixed64).
     *
     * @return iterable<array{int, int|string}>
     */
    private static function fields(string $bytes): iterable
    {
        $at = 0;
        $length = strlen($bytes);
        while ($at < $length) {
            $key = self::varint($bytes, $at);
            $wire = $key & 0x7;
            yield [$key >> 3, match ($wire) {
                0 => self::varint($bytes, $at),
                1 => self::take($bytes, $at, 8),
                2 => self::take($bytes, $at, self::varint($bytes, $at)),
                5 => self::take($bytes, $at, 4),
                default => throw StargazerException::invalidVectorTile("wire type {$wire} at byte {$at}."),
            }];
        }
    }

    /**
     * Packed varints, one at a time: geometry runs to millions of them, so the
     * bytes are read as int arrays 64 KB at a time (not char by char), and no
     * list of them all is ever built.
     *
     * @return Generator<int, int>
     */
    public static function varints(string $bytes): Generator
    {
        $value = 0;
        $shift = 0;
        foreach (str_split($bytes, 65536) as $chunk) {
            foreach (unpack('C*', $chunk) as $byte) {
                if ($byte < 0x80) {
                    yield $value | ($byte << $shift);
                    $value = 0;
                    $shift = 0;
                } else {
                    $value |= ($byte & 0x7F) << $shift;
                    $shift += 7;
                    if ($shift >= 64) {
                        throw StargazerException::invalidVectorTile('a number runs past ten bytes.');
                    }
                }
            }
        }
        if ($shift !== 0) {
            throw StargazerException::invalidVectorTile('a number runs past the end.');
        }
    }

    /**
     * @internal GibsVectorDictionary reads feature tags through this.
     *
     * @return list<int>
     */
    public static function packed(string $bytes): array
    {
        return iterator_to_array(self::varints($bytes), false);
    }

    private static function varint(string $bytes, int &$at): int
    {
        $value = 0;
        for ($shift = 0; $shift < 64; $shift += 7) {
            if ($at >= strlen($bytes)) {
                throw StargazerException::invalidVectorTile('a number runs past the end.');
            }
            $byte = ord($bytes[$at++]);
            $value |= ($byte & 0x7F) << $shift;
            if ($byte < 0x80) {
                return $value;
            }
        }

        throw StargazerException::invalidVectorTile('a number runs past ten bytes.');
    }

    private static function encode(int $value): string
    {
        $bytes = '';
        do {
            $byte = $value & 0x7F;
            $value = ($value >> 7) & (PHP_INT_MAX >> 6);
            $bytes .= chr($value !== 0 ? $byte | 0x80 : $byte);
        } while ($value !== 0);

        return $bytes;
    }

    private static function take(string $bytes, int &$at, int $count): string
    {
        if ($at + $count > strlen($bytes)) {
            throw StargazerException::invalidVectorTile('a field runs past the end.');
        }
        $value = substr($bytes, $at, $count);
        $at += $count;

        return $value;
    }
}
