<?php

namespace ProjectSaturnStudios\Stargazer\DONKI;

use ProjectSaturnStudios\Stargazer\DONKI\DataObjects\Cme;
use ProjectSaturnStudios\Stargazer\DONKI\DataObjects\CmeAnalysis;
use ProjectSaturnStudios\Stargazer\DONKI\DataObjects\Flare;
use ProjectSaturnStudios\Stargazer\DONKI\DataObjects\GeomagneticStorm;
use ProjectSaturnStudios\Stargazer\DONKI\DataObjects\HighSpeedStream;
use ProjectSaturnStudios\Stargazer\DONKI\DataObjects\InterplanetaryShock;
use ProjectSaturnStudios\Stargazer\DONKI\DataObjects\MagnetopauseCrossing;
use ProjectSaturnStudios\Stargazer\DONKI\DataObjects\Notification;
use ProjectSaturnStudios\Stargazer\DONKI\DataObjects\RadiationBeltEnhancement;
use ProjectSaturnStudios\Stargazer\DONKI\DataObjects\SolarEnergeticParticle;
use ProjectSaturnStudios\Stargazer\DONKI\DataObjects\WsaEnlilSimulation;
use ProjectSaturnStudios\Stargazer\DONKI\Enums\DonkiCatalog;
use ProjectSaturnStudios\Stargazer\DONKI\Enums\DonkiNotificationType;
use ProjectSaturnStudios\Stargazer\Enums\NasaURL;
use ProjectSaturnStudios\Stargazer\NasaApiService;
use ProjectSaturnStudios\Stargazer\PendingNasaRequest;

class DonkiAPIService extends NasaApiService
{
    public function cme(?string $from = null, ?string $to = null): PendingNasaRequest
    {
        return $this->donki('CME', 'cme', Cme::class, $from, $to);
    }

    public function cmeAnalysis(?string $from = null, ?string $to = null): PendingNasaRequest
    {
        return $this->donki('CMEAnalysis', 'cmeAnalysis', CmeAnalysis::class, $from, $to);
    }

    public function gst(?string $from = null, ?string $to = null): PendingNasaRequest
    {
        return $this->donki('GST', 'gst', GeomagneticStorm::class, $from, $to);
    }

    public function ips(
        ?string $from = null,
        ?string $to = null,
        ?string $location = null,
        DonkiCatalog|string|null $catalog = null,
    ): PendingNasaRequest {
        return $this->donki('IPS', 'ips', InterplanetaryShock::class, $from, $to, [
            'location' => $location,
            'catalog' => $catalog,
        ]);
    }

    public function flr(
        ?string $from = null,
        ?string $to = null,
        ?string $class = null,
        DonkiCatalog|string|null $catalog = null,
    ): PendingNasaRequest {
        return $this->donki('FLR', 'flr', Flare::class, $from, $to, [
            'class' => $class,
            'catalog' => $catalog,
        ]);
    }

    public function sep(?string $from = null, ?string $to = null): PendingNasaRequest
    {
        return $this->donki('SEP', 'sep', SolarEnergeticParticle::class, $from, $to);
    }

    public function mpc(?string $from = null, ?string $to = null): PendingNasaRequest
    {
        return $this->donki('MPC', 'mpc', MagnetopauseCrossing::class, $from, $to);
    }

    public function rbe(?string $from = null, ?string $to = null): PendingNasaRequest
    {
        return $this->donki('RBE', 'rbe', RadiationBeltEnhancement::class, $from, $to);
    }

    public function hss(?string $from = null, ?string $to = null): PendingNasaRequest
    {
        return $this->donki('HSS', 'hss', HighSpeedStream::class, $from, $to);
    }

    public function wsaEnlilSimulations(?string $from = null, ?string $to = null): PendingNasaRequest
    {
        return $this->donki('WSAEnlilSimulations', 'wsaEnlilSimulations', WsaEnlilSimulation::class, $from, $to);
    }

    public function notifications(
        ?string $from = null,
        ?string $to = null,
        DonkiNotificationType|string|null $type = null,
    ): PendingNasaRequest {
        return $this->donki('notifications', 'notifications', Notification::class, $from, $to, [
            'type' => $type,
        ]);
    }

    /**
     * @param  class-string  $dto
     * @param  array<string, mixed>  $extra
     */
    protected function donki(
        string $path,
        string $endpoint,
        string $dto,
        ?string $from,
        ?string $to,
        array $extra = [],
    ): PendingNasaRequest {
        return $this->pending(
            NasaURL::DONKI,
            $path,
            'stargazer.donki.'.$endpoint,
            $dto,
            $this->query(array_merge([
                'startDate' => $from,
                'endDate' => $to,
            ], $extra)),
        );
    }
}
