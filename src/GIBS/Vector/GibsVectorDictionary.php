<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Vector;

use ProjectSaturnStudios\Stargazer\Exceptions\StargazerException;

/** A vector tile layer's keys and values, which its features' tags index into. One per layer, shared. */
final readonly class GibsVectorDictionary
{
    /**
     * @param  list<string>  $keys
     * @param  list<string|int|float|bool|null>  $values
     */
    public function __construct(
        public array $keys,
        public array $values,
    ) {}

    /**
     * A feature's packed tags (key index, value index, …) as its properties.
     *
     * @return array<string, string|int|float|bool|null>
     * @throws StargazerException When a tag names a key or value the layer lacks.
     */
    public function properties(string $tags): array
    {
        $indexes = GibsVectorTile::packed($tags);
        $properties = [];
        for ($i = 0; $i + 1 < count($indexes); $i += 2) {
            if (! isset($this->keys[$indexes[$i]]) || ! array_key_exists($indexes[$i + 1], $this->values)) {
                throw StargazerException::invalidVectorTile("a feature names key {$indexes[$i]} or value {$indexes[$i + 1]}, which its layer lacks.");
            }
            $properties[$this->keys[$indexes[$i]]] = $this->values[$indexes[$i + 1]];
        }

        return $properties;
    }
}
