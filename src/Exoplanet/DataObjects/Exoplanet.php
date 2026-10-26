<?php

namespace ProjectSaturnStudios\Stargazer\Exoplanet\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;

/** One confirmed planet, a row of the archive's pscomppars table. Unknown values are null. */
final readonly class Exoplanet implements HydratesFromArray
{
    use HydratesNasaData;

    /** The pscomppars columns a planet is read from. */
    public const array COLUMNS = ['pl_name', 'hostname', 'discoverymethod', 'disc_year', 'pl_orbper', 'pl_rade', 'pl_bmasse', 'sy_dist', 'pl_eqt', 'st_teff', 'ra', 'dec'];

    public function __construct(
        public string $name,
        public string $host,
        public ?string $discoveryMethod,
        public ?int $discoveryYear,
        public ?float $orbitalPeriodDays,
        public ?float $radiusEarth,
        public ?float $massEarth,
        public ?float $distanceParsecs,
        public ?float $equilibriumKelvin,
        public ?float $starKelvin,
        public ?float $ra,
        public ?float $dec,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            name: self::text($data, 'pl_name'),
            host: self::text($data, 'hostname'),
            discoveryMethod: self::optionalText($data, 'discoverymethod'),
            discoveryYear: self::optionalInt($data, 'disc_year'),
            orbitalPeriodDays: self::optionalFloat($data, 'pl_orbper'),
            radiusEarth: self::optionalFloat($data, 'pl_rade'),
            massEarth: self::optionalFloat($data, 'pl_bmasse'),
            distanceParsecs: self::optionalFloat($data, 'sy_dist'),
            equilibriumKelvin: self::optionalFloat($data, 'pl_eqt'),
            starKelvin: self::optionalFloat($data, 'st_teff'),
            ra: self::optionalFloat($data, 'ra'),
            dec: self::optionalFloat($data, 'dec'),
        );
    }
}
