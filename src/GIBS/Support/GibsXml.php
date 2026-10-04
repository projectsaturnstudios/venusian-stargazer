<?php

namespace ProjectSaturnStudios\Stargazer\GIBS\Support;

use ProjectSaturnStudios\Stargazer\Exceptions\StargazerException;
use SimpleXMLElement;
use Voyager\Http\Client\Response;

/** The XML and response checks every GIBS hydrator shares. */
final class GibsXml
{
    public const string OWS = 'http://www.opengis.net/ows/1.1';

    public const string WMTS = 'http://www.opengis.net/wmts/1.0';

    public const string XLINK = 'http://www.w3.org/1999/xlink';

    /**
     * The document's root, after refusing an OGC exception report (WMTS
     * ExceptionReport or WMS ServiceExceptionReport) for what it says.
     *
     * @throws StargazerException
     */
    public static function load(string $xml, string $url): SimpleXMLElement
    {
        $previous = libxml_use_internal_errors(true);
        try {
            $root = simplexml_load_string($xml, options: LIBXML_NONET | LIBXML_COMPACT | LIBXML_PARSEHUGE);
            if ($root === false) {
                throw StargazerException::invalidXml($url, trim(libxml_get_last_error()->message ?? 'empty document'));
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        self::refuseException($root, $url);

        return $root;
    }

    /** @throws StargazerException When $root is an OGC exception report. */
    public static function refuseException(SimpleXMLElement $root, string $url): void
    {
        [$exception, $code, $text] = match ($root->getName()) {
            'ExceptionReport' => ['Exception', 'exceptionCode', 'ExceptionText'],
            'ServiceExceptionReport' => ['ServiceException', 'code', null],
            default => [null, null, null],
        };
        if (is_null($exception)) {
            return;
        }

        $first = $root->xpath("//*[local-name()='{$exception}']")[0] ?? $root;
        $message = is_null($text) ? (string) $first : (string) ($first->xpath("*[local-name()='{$text}']")[0] ?? '');

        throw StargazerException::serviceException($url, ((string) $first[$code]) ?: null, $message);
    }

    /**
     * An image or tile response, refused when GIBS answered with an exception
     * report instead (WMS does so with status 200).
     *
     * @throws StargazerException
     */
    public static function image(Response $response, string $url): Response
    {
        $type = strtolower((string) $response->header('Content-Type'));
        if (str_contains($type, 'xml') && ! str_contains($type, 'svg')) {
            self::load($response->body(), $url);

            throw StargazerException::serviceException($url, null, 'an XML document came back instead of an image.');
        }

        return $response;
    }

    /** "x y" corner text into [x, y]. @return array{float, float} */
    public static function corner(string $text): array
    {
        $parts = preg_split('/\s+/', trim($text));

        return [(float) ($parts[0] ?? 0), (float) ($parts[1] ?? 0)];
    }

    /** The first child named $name, whatever its namespace; null when there is none. */
    public static function first(SimpleXMLElement $element, string $name): ?SimpleXMLElement
    {
        return $element->xpath("*[local-name()='{$name}']")[0] ?? null;
    }

    /** @return list<SimpleXMLElement> Every child named $name, whatever its namespace. */
    public static function all(SimpleXMLElement $element, string $name): array
    {
        return $element->xpath("*[local-name()='{$name}']") ?: [];
    }

    /** The text of the first child named $name; null when there is none. */
    public static function text(SimpleXMLElement $element, string $name): ?string
    {
        $child = self::first($element, $name);

        return is_null($child) ? null : trim((string) $child);
    }

    /** The xlink:href of an element. */
    public static function href(SimpleXMLElement $element): string
    {
        return (string) $element->attributes(self::XLINK)['href'];
    }
}
