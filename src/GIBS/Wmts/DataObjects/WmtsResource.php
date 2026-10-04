<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Wmts\DataObjects;

use InvalidArgumentException;
use SimpleXMLElement;

/** A ResourceURL: a RESTful URL template for tiles or domains, with {Placeholders} to fill. */
final readonly class WmtsResource
{
    public function __construct(
        public string $format,
        public string $template,
        public string $type,
    ) {}

    public static function fromXml(SimpleXMLElement $resource): self
    {
        return new self((string) $resource['format'], (string) $resource['template'], (string) $resource['resourceType']);
    }

    /** @return list<string> The {Placeholder} names in the template. */
    public function placeholders(): array
    {
        preg_match_all('/\{(\w+)\}/', $this->template, $m);

        return $m[1];
    }

    /**
     * The template with its placeholders filled.
     *
     * @param  array<string, string|int>  $values  Placeholder name => value; every placeholder must be given.
     */
    public function fill(array $values): string
    {
        return preg_replace_callback('/\{(\w+)\}/', function (array $m) use ($values): string {
            if (! array_key_exists($m[1], $values)) {
                throw new InvalidArgumentException("No value for {{$m[1]}} in {$this->template}.");
            }

            return rawurlencode((string) $values[$m[1]]);
        }, $this->template);
    }
}
