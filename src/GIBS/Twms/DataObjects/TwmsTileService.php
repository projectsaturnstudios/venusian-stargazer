<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Twms\DataObjects;

use DOMDocument;
use ProjectSaturnStudios\Stargazer\Exceptions\StargazerException;
use ProjectSaturnStudios\Stargazer\GIBS\Support\GibsXml;
use XMLReader;

/** GetTileService: every layer's tile grid. Several megabytes, so read as a stream. */
final readonly class TwmsTileService
{
    /**
     * @param  array<string, TwmsTiledGroup>  $groups  By the layer each serves.
     */
    public function __construct(
        public string $title,
        public array $groups,
    ) {}

    /** @throws StargazerException */
    public static function fromXml(string $xml, string $url = 'TWMS GetTileService'): self
    {
        $reader = $xml === '' ? false : XMLReader::XML($xml, null, LIBXML_NONET | LIBXML_COMPACT | LIBXML_PARSEHUGE);
        if ($reader === false) {
            throw StargazerException::invalidXml($url, 'empty document');
        }

        $previous = libxml_use_internal_errors(true);
        $title = '';
        $groups = [];
        try {
            while (@$reader->read() && $reader->nodeType !== XMLReader::ELEMENT);
            if ($reader->localName !== 'WMS_Tile_Service') {
                GibsXml::load($xml, $url);

                throw StargazerException::invalidXml($url, "its root is <{$reader->localName}>, not <WMS_Tile_Service>");
            }
            // next() lands on the following sibling, so the loop reads on only when nothing skipped ahead.
            $more = @$reader->read();
            while ($more) {
                if ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'Title' && $title === '' && $reader->depth === 2) {
                    $title = trim($reader->readString());
                } elseif ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'TiledGroup') {
                    $group = TwmsTiledGroup::fromXml(simplexml_import_dom($reader->expand(new DOMDocument())));
                    $groups[$group->layer()] = $group;
                    $more = @$reader->next();

                    continue;
                }
                $more = @$reader->read();
            }
            if ($error = libxml_get_last_error()) {
                throw StargazerException::invalidXml($url, trim($error->message));
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            $reader->close();
        }

        return new self($title, $groups);
    }

    /** @throws StargazerException When no group serves that layer. */
    public function group(string $layer): TwmsTiledGroup
    {
        return $this->groups[$layer] ?? throw StargazerException::unknownGibsLayer($layer);
    }
}
