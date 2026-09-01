<?php

use ProjectSaturnStudios\Stargazer\Enums\NasaURL;
use ProjectSaturnStudios\Stargazer\NASA;
use ProjectSaturnStudios\Stargazer\NasaClient;
use Voyager\MagicAliases\MagicAlias;

it('catalogues every NASA host as an uppercase string-backed case', function () {
    foreach (NasaURL::cases() as $case) {
        expect($case->name)->toMatch('/^[A-Z][A-Z0-9_]*$/')
            ->and($case->value)->toStartWith('https://');
    }

    expect(NasaURL::EONET->value)->toBe('https://eonet.gsfc.nasa.gov/api/v3');
});

it('resolves the NASA magic alias to NasaClient', function () {
    expect(is_subclass_of(NASA::class, MagicAlias::class))->toBeTrue();

    $method = new ReflectionMethod(NASA::class, 'getMagicAliasAccessor');
    expect($method->invoke(null))->toBe(NasaClient::class);
});
