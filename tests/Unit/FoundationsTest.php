<?php

use ProjectSaturnStudios\Stargazer\Enums\NasaURL;
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