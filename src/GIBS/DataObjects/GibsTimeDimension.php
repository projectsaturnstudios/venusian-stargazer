<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\DataObjects;

use SimpleXMLElement;

/** A layer's time dimension, as WMTS and WMS list it: the instants it has imagery for. */
final readonly class GibsTimeDimension
{
    /**
     * @param  list<GibsTimePeriod>  $periods
     */
    public function __construct(
        public ?string $default,
        public bool $current,
        public array $periods,
    ) {}

    /** A WMTS <Dimension>: <Default>, <Current> and one <Value> per list of entries. */
    public static function fromWmts(SimpleXMLElement $dimension): self
    {
        $values = array_map('strval', iterator_to_array($dimension->Value, false));

        return new self(((string) $dimension->Default) ?: null, (string) $dimension->Current === 'true', self::periods(implode(',', $values)));
    }

    /** A WMS dimension or extent: its default attribute and comma-separated entries as text. */
    public static function fromWms(SimpleXMLElement $dimension): self
    {
        return new self(((string) $dimension['default']) ?: null, (string) $dimension['current'] === '1', self::periods((string) $dimension));
    }

    /** @return list<GibsTimePeriod> */
    private static function periods(string $entries): array
    {
        return array_values(array_map(GibsTimePeriod::parse(...), array_filter(array_map('trim', explode(',', $entries)), fn (string $e): bool => $e !== '')));
    }

    public function includes(string $when): bool
    {
        foreach ($this->periods as $period) {
            if ($period->includes($when)) {
                return true;
            }
        }

        return false;
    }

    /** The first instant there is imagery for. */
    public function earliest(): ?string
    {
        return $this->periods[0]->start ?? null;
    }

    /** The last instant there is imagery for. */
    public function latest(): ?string
    {
        return $this->periods === [] ? null : $this->periods[array_key_last($this->periods)]->end;
    }
}
