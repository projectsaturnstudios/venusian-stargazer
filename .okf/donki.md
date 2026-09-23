---
type: API Family
title: DONKI
description: Space Weather Database Of Notifications, Knowledge, Information — list-of-rows family.
tags:
  - donki
  - space-weather
  - core
status: draft
generated:
  by: claude-opus-5-5
  at: '2026-09-23T16:21:52Z'
sources:
  - id: service
    resource: src/DONKI/DonkiAPIService.php
    title: DonkiAPIService
  - id: docs
    resource: https://api.nasa.gov/
    title: NASA Open APIs DONKI section
---

# Overview

`nasa()->donki()` is the pattern-locking leaf. Date-range builders take `$from` / `$to` as `startDate` / `endDate`. List endpoints hydrate a `Collection` of DTOs.[^service][^docs]

# Endpoints

| Builder | Path | DTO |
|---------|------|-----|
| `cme($from, $to)` | `CME` | `Cme` |
| `cmeAnalysis($from, $to)` | `CMEAnalysis` | `CmeAnalysis` |
| `gst($from, $to)` | `GST` | `GeomagneticStorm` |
| `ips($from, $to, $location, $catalog)` | `IPS` | `InterplanetaryShock` |
| `flr($from, $to, $class, $catalog)` | `FLR` | `Flare` |
| `sep($from, $to)` | `SEP` | `SolarEnergeticParticle` |
| `mpc($from, $to)` | `MPC` | `MagnetopauseCrossing` |
| `rbe($from, $to)` | `RBE` | `RadiationBeltEnhancement` |
| `hss($from, $to)` | `HSS` | `HighSpeedStream` |
| `wsaEnlilSimulations($from, $to)` | `WSAEnlilSimulations` | `WsaEnlilSimulation` |
| `notifications($from, $to, $type)` | `notifications` | `Notification` |

Call names are `stargazer.donki.<endpoint>`. Catalogs and notification types are enums (`DonkiCatalog`, `DonkiNotificationType`, `DonkiIpsLocation`, `DonkiAnalysisFeature`). Host is `api.nasa.gov`, so `api_key` is appended.

The campaign example `nasa()->donki()->cme('2026-07-01','2026-08-01')->get()` returns a Collection of `Cme` against the captured fixture.

# Async

`async()` fulfils with a Collection of the endpoint's row DTOs. A non-success rejects with `StargazerException`.[^service]

# Related

* [Async lane](/async-seam.md) — list payload.

[^service]: DonkiAPIService
[^docs]: NASA Open APIs DONKI section
