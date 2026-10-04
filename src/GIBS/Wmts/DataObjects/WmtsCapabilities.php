<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Wmts\DataObjects;

use ProjectSaturnStudios\Stargazer\Exceptions\StargazerException;
use ProjectSaturnStudios\Stargazer\GIBS\Support\GibsXml;
use DOMDocument;
use XMLReader;

/**
 * A WMTS capabilities document: its layers and tile matrix sets. GIBS's run
 * to several megabytes, so they are read as a stream, one layer at a time.
 * Keep the XML text (the request's raw body) to build this again without
 * fetching it.
 */
final readonly class WmtsCapabilities
{
    /**
     * @param  array<string, WmtsLayer>  $layers  By identifier, in document order.
     * @param  array<string, WmtsTileMatrixSet>  $tileMatrixSets  By identifier.
     */
    public function __construct(
        public string $title,
        public array $layers,
        public array $tileMatrixSets,
    ) {}

    /** @throws StargazerException When the text is not a capabilities document, or is an exception report. */
    public static function fromXml(string $xml, string $url = 'WMTS capabilities'): self
    {
        $reader = $xml === '' ? false : XMLReader::XML($xml, null, LIBXML_NONET | LIBXML_COMPACT | LIBXML_PARSEHUGE);
        if ($reader === false) {
            throw StargazerException::invalidXml($url, 'empty document');
        }

        $previous = libxml_use_internal_errors(true);
        $title = '';
        $layers = [];
        $sets = [];
        try {
            if (! @$reader->read()) {
                throw StargazerException::invalidXml($url, trim(libxml_get_last_error()->message ?? 'empty document'));
            }
            while ($reader->nodeType !== XMLReader::ELEMENT && @$reader->read());
            if ($reader->localName !== 'Capabilities') {
                GibsXml::load($xml, $url);

                throw StargazerException::invalidXml($url, "its root is <{$reader->localName}>, not <Capabilities>");
            }

            // next() lands on the following sibling, so the loop reads on only when nothing skipped ahead.
            $more = @$reader->read();
            while ($more) {
                if ($reader->nodeType !== XMLReader::ELEMENT) {
                    $more = @$reader->read();

                    continue;
                }
                if ($reader->localName === 'Title' && $reader->namespaceURI === GibsXml::OWS && $title === '' && $reader->depth === 2) {
                    $title = trim($reader->readString());
                } elseif ($reader->localName === 'Layer' && $reader->depth === 2) {
                    $layer = WmtsLayer::fromXml(simplexml_import_dom($reader->expand(new DOMDocument())));
                    $layers[$layer->identifier] = $layer;
                    $more = @$reader->next();

                    continue;
                } elseif ($reader->localName === 'TileMatrixSet' && $reader->depth === 2) {
                    $set = WmtsTileMatrixSet::fromXml(simplexml_import_dom($reader->expand(new DOMDocument())));
                    $sets[$set->identifier] = $set;
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

        return new self($title, $layers, $sets);
    }

    /** @throws StargazerException When no layer has that identifier. */
    public function layer(string $identifier): WmtsLayer
    {
        return $this->layers[$identifier] ?? throw StargazerException::unknownGibsLayer($identifier);
    }

    /** @throws StargazerException When no set has that identifier. */
    public function tileMatrixSet(string $identifier): WmtsTileMatrixSet
    {
        return $this->tileMatrixSets[$identifier] ?? throw StargazerException::unknownTileMatrixSet($identifier);
    }

    /** The tile matrix set $layer is tiled in (its first, when several). */
    public function tileMatrixSetOf(string $layer): WmtsTileMatrixSet
    {
        return $this->tileMatrixSet($this->layer($layer)->tileMatrixSets[0] ?? '');
    }
}
