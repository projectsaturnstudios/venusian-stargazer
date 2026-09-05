---
type: API Family
title: DONKI
description: Space Weather Database Of Notifications, Knowledge, Information — list-of-rows envelope family.
tags:
  - donki
  - space-weather
  - core
status: draft
generated:
  by: cursor-grok-4.6/2026-09-04
  at: '2026-09-04T03:15:00Z'
sources:
  - id: service
    resource: src/DONKI/DonkiAPIService.php
    title: DonkiAPIService
  - id: arrived
    resource: src/DONKI/DonkiArrived.php
    title: DonkiArrived mail
  - id: docs
    resource: https://api.nasa.gov/
    title: NASA Open APIs DONKI section
---

# Overview

`NASA::donki()` is the pattern-locking leaf. Date-range builders take `$from` / `$to` as `startDate` / `endDate`. List endpoints hydrate a `Collection` of DTOs.[^service][^docs]

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

The campaign example `NASA::donki()->cme('2026-07-01','2026-08-01')->get()` returns a Collection of `Cme` against the captured fixture.

# Mail

`async()` on every builder keeps the class-string hydrator and adds an envelope inside `donki()`. The dock drains `DonkiArrived` (`array $items` of the endpoint's row DTOs) or `DonkiFailed`.[^service][^arrived]

# Related

* [Async envelope pattern](/async-envelope-pattern.md) — EPIC-shaped list payload.

[^service]: DonkiAPIService
[^arrived]: DonkiArrived mail
[^docs]: NASA Open APIs DONKI section
