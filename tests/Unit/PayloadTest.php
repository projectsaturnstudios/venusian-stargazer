<?php

use ProjectSaturnStudios\Stargazer\Enums\NasaPayload;
use ProjectSaturnStudios\Stargazer\Enums\NasaURL;
use ProjectSaturnStudios\Stargazer\Exceptions\StargazerException;
use ProjectSaturnStudios\Stargazer\PendingNasaRequest;
use Voyager\Http\Client\Factory;
use Voyager\Http\Client\Response;

function gibsPending(Factory $http, NasaPayload $payload, ?Closure $hydrator = null): PendingNasaRequest
{
    return new PendingNasaRequest(
        base: NasaURL::GIBS,
        path: 'wmts/epsg4326/best/1.0.0/WMTSCapabilities.xml',
        call_name: 'stargazer.gibs.test',
        hydrator: $hydrator,
        http: $http,
        payload: $payload,
    );
}

it('hands an XML payload to the hydrator as the body text', function () {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response('<Capabilities version="1.0.0"/>', 200, ['Content-Type' => 'text/xml']));

    $root = gibsPending($http, NasaPayload::XML, fn (string $xml): string => simplexml_load_string($xml)->getName())->get();

    expect($root)->toBe('Capabilities');
});

it('answers a bytes payload with the Response itself, or what its hydrator makes of it', function () {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response("\xff\xd8\xff\xe0jpeg", 200, ['Content-Type' => 'image/jpeg']));

    $response = gibsPending($http, NasaPayload::BYTES)->get();
    $length = gibsPending($http, NasaPayload::BYTES, fn (Response $r): int => strlen($r->body()))->get();

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->body())->toBe("\xff\xd8\xff\xe0jpeg")
        ->and($length)->toBe(8);
});

it('sends bytes and XML on the loop too', function () {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response('<Domains/>', 200));

    expect(gibsPending($http, NasaPayload::BYTES)->async()->wait()->body())->toBe('<Domains/>')
        ->and(gibsPending($http, NasaPayload::XML, fn (string $xml): string => $xml)->async()->wait())->toBe('<Domains/>');
});

it('applies its timeout to the request, on both lanes', function () {
    $http = stargazerHttp();
    $seen = [];
    $http->fake(function ($request, array $options) use (&$seen) {
        $seen[] = $options['timeout'] ?? null;

        return Factory::response('x');
    });

    gibsPending($http, NasaPayload::BYTES)->timeout(95)->get();
    gibsPending($http, NasaPayload::BYTES)->timeout(12.5)->async()->wait();
    gibsPending($http, NasaPayload::BYTES)->get();

    expect($seen)->toBe([95.0, 12.5, 30]);
});

it('keeps the timeout off the query and leaves the original request as it was', function () {
    $pending = gibsPending(stargazerHttp(), NasaPayload::XML);
    $longer = $pending->timeout(120);

    expect($longer)->not->toBe($pending)
        ->and($longer->query())->toBe([])
        ->and($longer->timeoutSeconds())->toBe(120.0)
        ->and($pending->timeoutSeconds())->toBeNull();
});

it('asks for gzip on XML, which GIBS compresses 30-fold', function () {
    $http = stargazerHttp();
    $encodings = [];
    $http->fake(function ($request, array $options) use (&$encodings) {
        $encodings[] = $options['decode_content'] ?? null;

        return Factory::response('<a/>');
    });

    gibsPending($http, NasaPayload::XML, fn (string $xml): string => $xml)->get();
    gibsPending($http, NasaPayload::BYTES)->get();

    expect($encodings)->toBe(['gzip', true]);   // true: Guzzle's default, decode if the server compressed anyway
});

it('still refuses a failed response, whatever the payload', function (NasaPayload $payload) {
    $http = stargazerHttp();
    $http->fake(fn () => Factory::response('<ExceptionReport/>', 400));

    expect(fn () => gibsPending($http, $payload, fn ($x) => $x)->get())
        ->toThrow(fn (StargazerException $e) => expect($e->status())->toBe(400));
})->with([NasaPayload::XML, NasaPayload::BYTES]);
