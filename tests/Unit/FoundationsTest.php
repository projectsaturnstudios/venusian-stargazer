<?php

use ProjectSaturnStudios\Stargazer\Enums\NasaURL;
use ProjectSaturnStudios\Stargazer\Exceptions\StargazerException;
use ProjectSaturnStudios\Stargazer\NasaClient;

it('catalogues every NASA host as an uppercase string-backed case', function () {
    foreach (NasaURL::cases() as $case) {
        expect($case->name)->toMatch('/^[A-Z][A-Z0-9_]*$/')
            ->and($case->value)->toStartWith('https://');
    }

    expect(NasaURL::EONET->value)->toBe('https://eonet.gsfc.nasa.gov/api/v3');
});

it('reaches the NasaClient through the nasa() helper', function () {
    $GLOBALS['__stargazer_test_bindings'] = ['nasa' => $client = stargazerClient(stargazerHttp())];

    expect(nasa())->toBe($client)->toBeInstanceOf(NasaClient::class);
});

it('carries the HTTP status on a failed request', function () {
    $http = stargazerHttp(loop: false);
    $http->fake(['*' => $http::response('slow down', 429)]);

    expect(fn () => stargazerClient($http)->apod()->date('2015-06-03')->get())
        ->toThrow(fn (StargazerException $e) => expect($e->status())->toBe(429));
});

it('has no status when no response was involved', function () {
    expect(StargazerException::loopNotBound()->status())->toBeNull();
});
