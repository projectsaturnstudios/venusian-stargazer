<?php

namespace ProjectSaturnStudios\Stargazer\Exoplanet;

use ProjectSaturnStudios\Stargazer\Enums\NasaURL;
use ProjectSaturnStudios\Stargazer\Exoplanet\DataObjects\Exoplanet;
use ProjectSaturnStudios\Stargazer\NasaApiService;
use ProjectSaturnStudios\Stargazer\PendingNasaRequest;
use Voyager\NutsAndBolts\Collection;

/**
 * The NASA Exoplanet Archive's TAP service: ADQL in, JSON rows out. A limit goes in maxrec,
 * never TOP: the archive applies TOP before ORDER BY, so TOP with an order picks the wrong rows.
 */
class ExoplanetArchive extends NasaApiService
{
    /** Any ADQL query. Answers a Collection of rows, each the row's columns by name. */
    public function adql(string $adql, ?int $limit = null): PendingNasaRequest
    {
        return $this->tap($adql, $limit, 'stargazer.exoplanet.adql', fn (array $rows): Collection => Collection::make($rows));
    }

    /**
     * Confirmed planets, one row each (pscomppars). Answers a Collection of Exoplanet.
     *
     * @param  string|null  $where  An ADQL condition, as is; null for every planet.
     */
    public function planets(?string $where = null, string $orderBy = 'disc_year desc, pl_name', ?int $limit = null): PendingNasaRequest
    {
        return $this->tap(self::select($where, $orderBy), $limit, 'stargazer.exoplanet.planets', Exoplanet::class);
    }

    /** The planet named $name. Answers an Exoplanet, or null when the archive has none by that name. */
    public function planet(string $name): PendingNasaRequest
    {
        $where = "pl_name = '".str_replace("'", "''", $name)."'";

        return $this->tap(self::select($where, null), 1, 'stargazer.exoplanet.planet', fn (array $rows): ?Exoplanet => isset($rows[0]) ? Exoplanet::fromArray((array) $rows[0]) : null);
    }

    protected static function select(?string $where, ?string $orderBy): string
    {
        return 'select '.implode(',', Exoplanet::COLUMNS).' from pscomppars'
            .(is_null($where) ? '' : ' where '.$where)
            .(is_null($orderBy) ? '' : ' order by '.$orderBy);
    }

    /** @param  \Closure(array):mixed|class-string  $hydrator */
    protected function tap(string $adql, ?int $limit, string $call_name, \Closure|string $hydrator): PendingNasaRequest
    {
        return $this->pending(
            base: NasaURL::EXOPLANET,
            path: 'sync',
            call_name: $call_name,
            hydrator: $hydrator,
            query: $this->query(['query' => $adql, 'format' => 'json', 'maxrec' => $limit]),
        );
    }
}
